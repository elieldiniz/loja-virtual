<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ComingSoon extends Component
{
    public string $heading = '';

    public string $current = 'dash';

    public function render()
    {
        return view('livewire.admin.coming-soon', ['current' => $this->current])->layoutData(['current' => $this->current]);
    }
}
