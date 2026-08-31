<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Utilisateur du back-office. Le portfolio n'a qu'un administrateur, mais
 * l'entité reste générique pour permettre l'ajout de comptes (cf. issue #12).
 */
#[ApiResource(
    description: 'Comptes du back-office.',
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
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
     * @var list<string>
     */
    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    /**
     * Mot de passe hashé — jamais exposé par l'API (cf. issue #6).
     */
    #[ORM\Column]
    private string $password = '';

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
        $roles[] = 'ROLE_USER';

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

    public function eraseCredentials(): void
    {
        // Aucune donnée sensible temporaire n'est stockée sur l'entité.
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
