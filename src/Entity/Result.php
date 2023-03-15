<?php

namespace App\Entity;

use App\Repository\ResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultRepository::class)]
class Result
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column]
    private ?int $images_total = null;

    #[ORM\Column]
    private ?int $time_spent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getImagesTotal(): ?int
    {
        return $this->images_total;
    }

    public function setImagesTotal(int $images_total): self
    {
        $this->images_total = $images_total;

        return $this;
    }

    public function getTimeSpent(): ?int
    {
        return $this->time_spent;
    }

    public function setTimeSpent(int $time_spent): self
    {
        $this->time_spent = $time_spent;

        return $this;
    }

    public function getTimeSpentInSeconds()
    {
        return $this->time_spent / 1000;
    }
}
