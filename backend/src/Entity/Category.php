<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Catégorie thématique (Nature, Spectacle, Espace, Voiture, Événements…).
 * Une photo et un album appartiennent chacun à une seule catégorie.
 */
#[ApiResource(
    description: 'Catégories thématiques utilisées pour classer les photos et les albums.',
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['category:read']],
    denormalizationContext: ['groups' => ['category:write']],
    order: ['name' => 'ASC'],
    paginationEnabled: false,
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'ipartial', 'slug' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['name'], arguments: ['orderParameterName' => 'order'])]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'category')]
#[UniqueEntity(fields: ['name'], message: 'Cette catégorie existe déjà.')]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug est déjà utilisé.')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category:read', 'photo:read', 'album:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['category:read', 'category:write', 'photo:read', 'album:read'])]
    private string $name = '';

    /**
     * Identifiant lisible utilisé dans les URLs publiques (/galerie?categorie=nature).
     */
    #[ORM\Column(length: 60, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Le slug ne peut contenir que des minuscules, des chiffres et des tirets.')]
    #[Groups(['category:read', 'category:write', 'photo:read', 'album:read'])]
    private string $slug = '';

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'category')]
    private Collection $photos;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'category')]
    private Collection $albums;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->albums = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /**
     * Compteur affiché dans le back-office (« Nature · 51 photos »).
     */
    #[Groups(['category:read'])]
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
            $photo->setCategory($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        $this->photos->removeElement($photo);

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
            $album->setCategory($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        $this->albums->removeElement($album);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
