<?php

namespace App\Services\Delivery\DTO;

readonly class CargoPackage
{
    public function __construct(
        public float $widthCm,
        public float $lengthCm,
        public float $heightCm,
        public float $weightKg,
        public int $count = 1,
    ) {}
}
