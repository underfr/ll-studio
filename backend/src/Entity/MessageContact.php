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
use App\Repository\MessageContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message envoyé depuis le formulaire de contact public, consulté depuis
 * l'écran « Messages » du back-office.
 */
#[ApiResource(
    shortName: 'Message',
    description: 'Messages reçus via le formulaire de contact public.',
    operations: [
        new Post(
            security: "is_granted('PUBLIC_ACCESS')",
            openapi: new OpenApiOperation(
                summary: 'Envoyer un message',
                description: "Endpoint du formulaire de contact public : c'est la seule opération de cette ressource "
                    ."ouverte aux visiteurs. Le message est enregistré comme non lu.",
            ),
        ),
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')",
            openapi: new OpenApiOperation(
                summary: 'Lister les messages reçus',
                description: "Boîte de réception du back-office, triée du plus récent au plus ancien. `?read=false` "
                    ."renvoie les messages non lus, qui alimentent le badge du tableau de bord.",
            ),
        ),
        new Get(security: "is_granted('ROLE_ADMIN')", openapi: new OpenApiOperation(summary: 'Consulter un message')),
        // Le back-office ne modifie qu'un seul champ : le marqueur « lu ».
        new Patch(
            denormalizationContext: ['groups' => ['message:update']],
            security: "is_granted('ROLE_ADMIN')",
            openapi: new OpenApiOperation(
                summary: 'Marquer un message comme lu',
                description: "Seul le champ `read` est modifiable : le contenu d'un message reçu ne peut pas être réécrit.",
            ),
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')", openapi: new OpenApiOperation(summary: 'Supprimer un message')),
    ],
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']],
    order: ['createdAt' => 'DESC'],
    paginationItemsPerPage: 25,
)]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'exact', 'subject' => 'ipartial', 'name' => 'ipartial'])]
#[ApiFilter(BooleanFilter::class, properties: ['read'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt'], arguments: ['orderParameterName' => 'order'])]
#[ORM\Entity(repositoryClass: MessageContactRepository::class)]
#[ORM\Table(name: 'message_contact')]
#[ORM\Index(name: 'idx_message_read_created', columns: ['is_read', 'created_at'])]
class MessageContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Merci d’indiquer votre nom.')]
    #[Assert\Length(max: 100)]
    #[Groups(['message:read', 'message:write'])]
    private string $name = '';

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Merci d’indiquer votre adresse e-mail.')]
    #[Assert\Email(message: 'Cette adresse e-mail n’est pas valide.')]
    #[Assert\Length(max: 180)]
    #[Groups(['message:read', 'message:write'])]
    private string $email = '';

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Merci d’indiquer un sujet.')]
    #[Assert\Length(max: 150)]
    #[Groups(['message:read', 'message:write'])]
    private string $subject = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Le message ne peut pas être vide.')]
    #[Assert\Length(min: 10, max: 5000)]
    #[ApiProperty(description: 'Corps du message, entre 10 et 5000 caractères.')]
    #[Groups(['message:read', 'message:write'])]
    private string $message = '';

    /**
     * Alimente le compteur « 2 non lus » du tableau de bord.
     */
    #[ORM\Column(name: 'is_read')]
    #[ApiProperty(description: "Passe à true quand le message a été ouvert dans le back-office. Seul champ modifiable par PATCH.", example: false)]
    #[Groups(['message:read', 'message:update'])]
    private bool $read = false;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['message:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function setRead(bool $read): static
    {
        $this->read = $read;

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
}
