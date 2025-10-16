<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $photographer = null;

    #[ORM\Column(type: "string", length: 100)]
    private string $title;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "boolean")]
    private bool $isPublic = true;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "string", length: 20, options: ["default" => "approved"])]
    private string $status = 'approved';

    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: "album")]
    private Collection $photos;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters below...

    public function getId(): ?int { return $this->id; }
    public function getPhotographer(): ?User { return $this->photographer; }
    public function setPhotographer(?User $photographer): self { $this->photographer = $photographer; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function isPublic(): bool { return $this->isPublic; }
    public function setIsPublic(bool $isPublic): self { $this->isPublic = $isPublic; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getPhotos(): Collection { return $this->photos; }
    public function addPhoto(Photo $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos[] = $photo;
            $photo->setAlbum($this);
        }
        return $this;
    }
    public function removePhoto(Photo $photo): self
    {
        if ($this->photos->contains($photo)) {
            $this->photos->removeElement($photo);
            if ($photo->getAlbum() === $this) {
                $photo->setAlbum(null);
            }
        }
        return $this;
    }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}