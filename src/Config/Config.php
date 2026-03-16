<?php 

namespace App\Config;

enum BalancerThreshold {
    case SUITABLE_MACHINE_THRESHOLD;

    public function value(): float {
        return match($this) {
            self::SUITABLE_MACHINE_THRESHOLD => 0.9,
        };
    }
}