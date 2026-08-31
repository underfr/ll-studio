<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
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
use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Série photographique regroupant plusieurs photos autour d'un même sujet
 * (« Aurores Boréales », « Puy du Fou 2024 »…). Une photo peut figurer dans
 * plusieurs albums.
 */
#[ApiResource(
    description: 'Séries photographiques regroupant plusieurs photos.',
    operations: [
        new GetCollection(
            security: "is_granted('PUBLIC_ACCESS')",
            openapi: new OpenApiOperation(
                summary: 'Lister les séries',
                description: "Collection paginée (12 éléments par défaut), triée de la plus récente à la plus ancienne.\n\n"
                    ."Chaque série est renvoyée sans sa liste de photos : seuls la couverture (`coverPhoto`) et le compteur "
                    ."(`photoCount`) sont exposés, ce qui suffit à la grille de la page Galerie.",
            ),
        ),
        new Get(
            normalizationContext: ['groups' => ['album:read', 'album:item:read']],
            security: "is_granted('PUBLIC_ACCESS')",
            openapi: new OpenApiOperation(
                summary: 'Consulter une série',
                description: "Embarque cette fois la liste complète des photos de la série, dans l'ordre d'ajout.",
            ),
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            openapi: new OpenApiOperation(
                summary: 'Créer une série',
                description: "Réservé au back-office. `photos` accepte un tableau d'IRI de photos existantes.",
            ),
        ),
        new Patch(
            security: "is_granted('CONTENT_EDIT', object)",
            openapi: new OpenApiOperation(
                summary: 'Modifier une série',
                description: "Mise à jour partielle. Transmettre `photos` remplace intégralement la composition de la série.",
            ),
        ),
        new Delete(
            security: "is_granted('CONTENT_DELETE', object)",
            openapi: new OpenApiOperation(
                summary: 'Supprimer une série',
                description: 'Les photos ne sont pas supprimées : seules les liaisons de la série le sont.',
            ),
        ),
    ],
    normalizationContext: ['groups' => ['album:read']],
    denormalizationContext: ['groups' => ['album:write']],
    order: ['createdAt' => 'DESC'],
    paginationItemsPerPage: 12,
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'ipartial',
    'slug' => 'exact',
    'category' => 'exact',
    'category.slug' => 'exact',
])]
#[ApiFilter(BooleanFilter::class, properties: ['visible'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'title'], arguments: ['orderParameterName' => 'order'])]
#[ORM\Entity(repositoryClass: AlbumRepository::class)]
#[ORM\Table(name: 'album')]
#[ORM\Index(name: 'idx_album_visible_created', columns: ['visible', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug est déjà utilisé par un autre album.')]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['album:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[Groups(['album:read', 'album:write'])]
    private string $title = '';

    /**
     * Identifiant lisible utilisé dans l'URL publique (/albums/aurores-boreales).
     */
    #[ORM\Column(length: 140, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 140)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Le slug ne peut contenir que des minuscules, des chiffres et des tirets.')]
    #[ApiProperty(description: "Identifiant lisible utilisé dans l'URL publique de la série.", example: 'puy-du-fou-2024')]
    #[Groups(['album:read', 'album:write'])]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['album:read', 'album:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['album:read', 'album:write'])]
    private bool $visible = true;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['album:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['album:item:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'albums')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Un album doit appartenir à une catégorie.')]
    #[Groups(['album:read', 'album:write'])]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'albums')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['album:item:read'])]
    private ?User $owner = null;

    /**
     * Photo utilisée en bandeau sur la page de l'album. Si elle n'est pas
     * renseignée, le front retombe sur la première photo de la série.
     */
    #[ORM\ManyToOne(targetEntity: Photo::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[ApiProperty(description: "Photo affichée en bandeau. Si elle est absente, le front retombe sur la première photo de la série.")]
    #[Groups(['album:read', 'album:write'])]
    private ?Photo $coverPhoto = null;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\ManyToMany(targetEntity: Photo::class, inversedBy: 'albums')]
    #[ORM\JoinTable(name: 'album_photo')]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'photo_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ApiProperty(description: "Photos de la série. En écriture, transmettre un tableau d'IRI ; le tableau remplace la composition existante.")]
    #[Groups(['album:item:read', 'album:write'])]
    private Collection $photos;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->photos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getCoverPhoto(): ?Photo
    {
        return $this->coverPhoto;
    }

    public function setCoverPhoto(?Photo $coverPhoto): static
    {
        $this->coverPhoto = $coverPhoto;

        return $this;
    }

    /**
     * Nombre de photos affiché sous le titre de l'album (« 24 photos »).
     */
    #[Groups(['album:read'])]
    public function getPhotoCount(): int
    {
        return $this->photos->count();
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
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        $this->photos->removeElement($photo);

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
