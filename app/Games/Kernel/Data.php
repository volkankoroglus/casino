<?php namespace App\Games\Kernel;

use App\Games\Kernel\Module\General\HouseEdgeModule;
use App\Models\Modules;
use App\Models\User;
use Illuminate\Support\Facades\Log;


class Data {

    private $data;
    private ?User $user;

    function __construct(?User $user, array $data) {
        $this->user = $user;
        $this->data = $data;
    }

    public function guest(): bool {
        return $this->user == null;
    }

    public function user() {
        return $this->user;
    }

    public function id(): string {
        return strval($this->data['api_id']);
    }

    public function currency(): string {
        return strval($this->data['currency']);
    }

    public function demo(): bool {
        return filter_var($this->data['demo'], FILTER_VALIDATE_BOOLEAN);
    }

    public function quick(): bool {
        return filter_var($this->data['quick'], FILTER_VALIDATE_BOOLEAN);
    }

    public function autoCashoutMultiplier(): float {
       // $floatval = floatval($this->data['autoCashout']);
        Log::info('autocashout multiplier is: ' . floatval($this->data['autoCashoutMultiplier']));
        return floatval($this->data['autoCashoutMultiplier']);
    }

    public function bet(float $set = null): float {
        if($set != null) $this->data['bet'] = $set;
        return floatval($this->data['bet']);
    }

    public function game(): object {
        return (object) $this->data['data'];
    }

    public function intArray($name): array {
        return array_map(function($value) {
            return intval($value);
        }, $this->data['data'][$name]);
    }

    public function toArray(): array {
        return $this->data;
    }

}
