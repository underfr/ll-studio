<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Security\Role;
use App\State\UserPasswordHasherProcessor;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Utilisateur du back-office. Le portfolio n'a qu'un administrateur, mais
 * l'entité reste générique pour permettre l'ajout de comptes (cf. issue #12).
 */
#[ApiResource(
    description: 'Comptes du back-office.',
    operations: [
        new GetCollection(openapi: new OpenApiOperation(summary: 'Lister les comptes')),
        new Get(openapi: new OpenApiOperation(summary: 'Consulter un compte')),
        new Post(
            processor: UserPasswordHasherProcessor::class,
            validationContext: ['groups' => ['Default', 'user:create']],
            openapi: new OpenApiOperation(
                summary: 'Créer un compte',
                description: "Le mot de passe se transmet en clair dans `plainPassword` : il est haché avant "
                    ."enregistrement et n'est jamais relu. La colonne `password` n'est exposée par aucun groupe.",
            ),
        ),
        new Patch(
            processor: UserPasswordHasherProcessor::class,
            openapi: new OpenApiOperation(
                summary: 'Modifier un compte',
                description: "Transmettre `plainPassword` change le mot de passe ; l'omettre le laisse inchangé.",
            ),
        ),
        new Delete(openapi: new OpenApiOperation(summary: 'Supprimer un compte')),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    order: ['lastName' => 'ASC'],
)]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'exact', 'lastName' => 'ipartial'])]
#[ApiFilter(OrderFilter::class, properties: ['lastName', 'createdAt'], arguments: ['orderParameterName' => 'order'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    #[Groups(['user:read', 'user:write'])]
    private string $email = '';

    /**
     * Rôles explicitement accordés au compte. ROLE_USER est ajouté à la
     * volée par getRoles() et n'a donc pas à figurer ici.
     *
     * @var list<string>
     */
    #[ApiProperty(
        description: 'Rôles accordés au compte. ROLE_USER est implicite.',
        example: ['ROLE_ADMIN'],
    )]
    #[Assert\All([
        new Assert\Choice(callback: [Role::class, 'values'], message: 'Le rôle « {{ value }} » n’existe pas.'),
    ])]
    #[Assert\Unique(message: 'Ce rôle est présent plusieurs fois.')]
    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    /**
     * Mot de passe haché — jamais exposé par l'API (cf. issue #6).
     */
    #[ORM\Column]
    private string $password = '';

    /**
     * Mot de passe en clair reçu à la création ou à la modification d'un
     * compte. Il n'est pas persisté : UserPasswordHasherProcessor le hache
     * puis vide cette propriété.
     */
    #[ApiProperty(description: 'Mot de passe en clair. Écriture seule : il est haché puis oublié.', example: 'Temporaire123!')]
    #[Groups(['user:write'])]
    #[Assert\NotBlank(groups: ['user:create'])]
    #[Assert\Length(min: 8, max: 4096)]
    private ?string $plainPassword = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['user:read', 'user:write'])]
    private string $firstName = '';

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['user:read', 'user:write'])]
    private string $lastName = '';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['user:read'])]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'owner')]
    private Collection $photos;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'owner')]
    private Collection $albums;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->photos = new ArrayCollection();
        $this->albums = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Identifiant unique utilisé par le composant Security.
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Tout utilisateur authentifié possède au minimum ROLE_USER.
        $roles[] = Role::USER->value;

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Efface le mot de passe en clair dès qu'il a été haché.
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isAdmin(): bool
    {
        return \in_array(Role::ADMIN->value, $this->getRoles(), true);
    }

    #[Groups(['user:read'])]
    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setOwner($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo) && $photo->getOwner() === $this) {
            $photo->setOwner(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setOwner($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album) && $album->getOwner() === $this) {
            $album->setOwner(null);
        }

        return $this;
    }
}
