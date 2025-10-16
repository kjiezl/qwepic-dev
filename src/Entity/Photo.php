<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $photographer = null;

    #[ORM\ManyToOne(targetEntity: Album::class, inversedBy: "photos")]
    private ?Album $album = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $src;

    #[ORM\Column(type: "string", length: 100)]
    private string $title;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $tags = [];

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $thumbnails = [];

    #[ORM\Column(type: "boolean")]
    private bool $isPublic = true;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "string", length: 20, options: ["default" => "approved"])]
    private string $status = 'approved';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters below...

    public function getId(): ?int { return $this->id; }
    public function getPhotographer(): ?User { return $this->photographer; }
    public function setPhotographer(?User $photographer): self { $this->photographer = $photographer; return $this; }
    public function getAlbum(): ?Album { return $this->album; }
    public function setAlbum(?Album $album): self { $this->album = $album; return $this; }
    public function getSrc(): string { return $this->src; }
    public function setSrc(string $src): self { $this->src = $src; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getTags(): ?array { return $this->tags; }
    public function setTags(?array $tags): self { $this->tags = $tags; return $this; }
    public function isPublic(): bool { return $this->isPublic; }
    public function setIsPublic(bool $isPublic): self { $this->isPublic = $isPublic; return $this; }
    public function getThumbnails(): ?array { return $this->thumbnails; }
    public function setThumbnails(?array $thumbnails): self { $this->thumbnails = $thumbnails; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}