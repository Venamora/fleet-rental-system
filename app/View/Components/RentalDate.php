<?php
namespace App\View\Components;
use Carbon\CarbonInterface;
use Illuminate\View\Component;
use Illuminate\View\View;
final class RentalDate extends Component
{
    public function __construct(public mixed $date, public bool $endOfDay = false) {}
    public function render(): View { return view('components.rental-date'); }
    public function formatted(): string
    {
        $date = $this->date instanceof CarbonInterface ? $this->date : now()->parse((string) $this->date);
        $months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return $date->day.' '.$months[$date->month - 1].' '.$date->year.($this->endOfDay ? ' (hingga akhir hari, WIB)' : '');
    }
}
