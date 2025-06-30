<?php

namespace Concrete\Package\PopulationImporter\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Population Entity
 * 
 * Represents population data for Japanese prefectures by year
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="population_data",
 *     options={"charset":"utf8mb4","collate":"utf8mb4_unicode_ci"},
 *     indexes={
 *         @ORM\Index(name="idx_prefecture_year", columns={"prefecture", "year"}),
 *         @ORM\Index(name="idx_prefecture", columns={"prefecture"}),
 *         @ORM\Index(name="idx_year", columns={"year"})
 *     }
 * )
 */
class Population
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $prefecture;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $year;

    /**
     * @ORM\Column(type="bigint", nullable=false)
     */
    protected $population;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $prefecture_code;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $updated_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrefecture(): ?string
    {
        return $this->prefecture;
    }

    public function setPrefecture(string $prefecture): self
    {
        $this->prefecture = $prefecture;
        $this->updated_at = new \DateTime();
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        $this->updated_at = new \DateTime();
        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(int $population): self
    {
        $this->population = $population;
        $this->updated_at = new \DateTime();
        return $this;
    }


    public function getPrefectureCode(): ?string
    {
        return $this->prefecture_code;
    }

    public function setPrefectureCode(?string $prefecture_code): self
    {
        $this->prefecture_code = $prefecture_code;
        $this->updated_at = new \DateTime();
        return $this;
    }
    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }


    public function setUpdatedAt(\DateTime $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }
}