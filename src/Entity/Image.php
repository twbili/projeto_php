<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;

use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[Vich\Uploadable(  )]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: 'image', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $imgUpdatedAt = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getImageFile(): ?File{
        return $this->imageFile;
    }
    public function setImageFile(?File $imageFile=null): void{
        $this->imageFile = $imageFile;
        if(null !== $imageFile){
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImgUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->imgUpdatedAt;
    }

    public function setImgUpdatedAt(?\DateTimeImmutable $imgUpdatedAt): void
    {
        $this->imgUpdatedAt = $imgUpdatedAt;
    }


}
