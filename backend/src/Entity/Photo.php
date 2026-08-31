<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
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
use App\Repository\PhotoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Une photographie de la galerie. Le fichier lui-même est stocké sur le
 * disque ; l'entité n'en conserve que le chemin relatif (cf. issue #14).
 */
#[ApiResource(
    description: 'Photographies de la galerie.',
    operations: [
        new GetCollection(
            security: "is_granted('PUBLIC_ACCESS')",
            openapi: new OpenApiOperation(
                summary: 'Lister les photographies',
                description: "Collection paginée (24 éléments par défaut), triée de la plus récente à la plus ancienne.\n\n"
                    ."Filtres disponibles : `category.slug`, `albums.slug`, `visible`, `createdAt[after]`/`createdAt[before]`, "
                    ."recherche insensible à la casse sur `title` et `description`, tri via `order[createdAt]` ou `order[title]`.",
            ),
        ),
        new Get(
            normalizationContext: ['groups' => ['photo:read', 'photo:item:read']],
            security: "is_granted('PUBLIC_ACCESS')",
            openapi: new OpenApiOperation(
                summary: 'Consulter une photographie',
                description: "Ajoute à la vue liste la date de dernière modification, l'auteur et les albums dans lesquels la photo apparaît.",
            ),
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            openapi: new OpenApiOperation(
                summary: 'Ajouter une photographie',
                description: "Réservé au back-office. Cette opération enregistre les métadonnées ; l'envoi du fichier disposera de son propre endpoint sécurisé (issue #16).",
            ),
        ),
        new Patch(
            security: "is_granted('CONTENT_EDIT', object)",
            openapi: new OpenApiOperation(
                summary: 'Modifier une photographie',
                description: 'Mise à jour partielle : seuls les champs transmis sont modifiés.',
            ),
        ),
        new Delete(
            security: "is_granted('CONTENT_DELETE', object)",
            openapi: new OpenApiOperation(summary: 'Supprimer une photographie'),
        ),
    ],
    normalizationContext: ['groups' => ['photo:read']],
    denormalizationContext: ['groups' => ['photo:write']],
    order: ['createdAt' => 'DESC'],
    paginationItemsPerPage: 24,
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'ipartial',
    'description' => 'ipartial',
    'category' => 'exact',
    'category.slug' => 'exact',
    'albums' => 'exact',
    'albums.slug' => 'exact',
])]
#[ApiFilter(BooleanFilter::class, properties: ['visible'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'title'], arguments: ['orderParameterName' => 'order'])]
#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\Table(name: 'photo')]
#[ORM\Index(name: 'idx_photo_visible_created', columns: ['visible', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['photo:read', 'album:read', 'album:item:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[ApiProperty(description: 'Titre affiché sous la photo et dans la lightbox.', example: 'Légende orange à Nogaro')]
    #[Groups(['photo:read', 'photo:write', 'album:read', 'album:item:read'])]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['photo:read', 'photo:write', 'album:item:read'])]
    private ?string $description = null;

    /**
     * Texte alternatif, obligatoire pour l'accessibilité (cf. issue #33).
     */
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Le texte alternatif est obligatoire pour l'accessibilité.")]
    #[ApiProperty(description: "Texte alternatif, obligatoire : il est lu par les lecteurs d'écran et affiché si l'image ne charge pas.", example: 'Porsche Jägermeister orange n°64 sur le circuit de Nogaro')]
    #[Groups(['photo:read', 'photo:write', 'album:read', 'album:item:read'])]
    private string $alt = '';

    /**
     * Nom du fichier sur le disque, renseigné par VichUploader.
     */
    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ApiProperty(
        description: "Nom du fichier stocké sur le disque. Renseigné par VichUploader à l'envoi du fichier, pas modifiable directement.",
        example: 'legende-orange-a-nogaro-68b3f1c2d4e5a.jpg',
    )]
    #[Groups(['photo:read', 'album:read', 'album:item:read'])]
    private string $filePath = '';

    /**
     * Fichier envoyé. Il n'est pas persisté : VichUploader l'écrit sur le
     * disque et renseigne $filePath.
     *
     * Les contraintes ci-dessous sont la dernière barrière avant l'écriture
     * sur le disque : le contrôle côté client est une commodité, pas une
     * sécurité, et un appel direct à l'API le contourne.
     */
    #[Assert\Image(
        // Plafond volontairement sous les limites de PHP (upload_max_filesize
        // 12M) et de Nginx (client_max_body_size 16m), pour que le dépassement
        // remonte comme une erreur de validation lisible plutôt qu'en 413.
        maxSize: '8M',
        maxSizeMessage: "L'image ne doit pas dépasser {{ limit }} {{ suffix }} (fichier reçu : {{ size }} {{ suffix }}).",
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Format non accepté ({{ type }}). Formats autorisés : JPEG, PNG et WebP.',
        // En dessous, l'image ne tient pas la grille de la galerie ni
        // l'affichage en lightbox.
        minWidth: 800,
        minWidthMessage: "L'image fait {{ width }} px de large, le minimum est {{ min_width }} px.",
        minHeight: 600,
        minHeightMessage: "L'image fait {{ height }} px de haut, le minimum est {{ min_height }} px.",
        // Au-delà, c'est un fichier d'archive : il n'a rien à faire sur le web.
        maxWidth: 8000,
        maxWidthMessage: "L'image fait {{ width }} px de large, le maximum est {{ max_width }} px.",
        maxHeight: 8000,
        maxHeightMessage: "L'image fait {{ height }} px de haut, le maximum est {{ max_height }} px.",
        // Un fichier dont l'en-tête annonce une image mais que GD ne sait pas
        // décoder est rejeté : l'extension et le type MIME se falsifient.
        detectCorrupted: true,
        corruptedMessage: "Le fichier n'est pas une image exploitable ou est endommagé.",
    )]
    #[Vich\UploadableField(mapping: 'photo_image', fileNameProperty: 'filePath')]
    private ?File $imageFile = null;

    /**
     * URL publique de l'image, calculée par PhotoContentUrlNormalizer à
     * partir du mapping VichUploader. En lecture seule.
     */
    #[ApiProperty(
        description: "URL publique de l'image, servie par Nginx.",
        example: '/uploads/photos/legende-orange-a-nogaro-68b3f1c2d4e5a.jpg',
        writable: false,
    )]
    #[Groups(['photo:read', 'album:read', 'album:item:read'])]
    private ?string $contentUrl = null;

    /**
     * Une photo masquée reste en base mais disparaît du site public.
     */
    #[ORM\Column]
    #[ApiProperty(description: 'Une photo masquée reste en base mais disparaît du site public.', example: true)]
    #[Groups(['photo:read', 'photo:write'])]
    private bool $visible = true;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['photo:read', 'album:item:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['photo:item:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Une photo doit appartenir à une catégorie.')]
    #[Groups(['photo:read', 'photo:write'])]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['photo:item:read'])]
    private ?User $owner = null;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'photos')]
    #[Groups(['photo:item:read'])]
    private Collection $albums;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->albums = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAlt(): string
    {
        return $this->alt;
    }

    public function setAlt(string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * Doctrine ne détecte un changement que sur une propriété mappée : on
     * touche updatedAt pour que VichUploader soit déclenché même si aucun
     * autre champ n'a bougé.
     */
    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    public function setContentUrl(?string $contentUrl): static
    {
        $this->contentUrl = $contentUrl;

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
            $album->addPhoto($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            $album->removePhoto($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
