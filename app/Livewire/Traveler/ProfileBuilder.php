<?php

namespace App\Livewire\Traveler;

use Livewire\Component;
use App\Models\UserProfile;

class ProfileBuilder extends Component
{
    public int    $step       = 1;
    public string $homeCity   = '';
    public string $dailyBudgetDisplay = '';
    public float  $dailyBudget = 0;

    public array $selectedInterests    = [];
    public array $selectedSubInterests = [];

    public const INTERESTS = [
        'Beach'           => ['Surfing', 'Snorkeling', 'Island Hopping', 'Swimming'],
        'Nature'          => ['Mountains', 'Waterfalls', 'Wildlife', 'Forests'],
        'Food Trip'       => ['Street Food', 'Fine Dining', 'Local Delicacies', 'Cafes'],
        'Adventure'       => ['Hiking', 'Diving', 'Ziplining', 'Canyoneering'],
        'Historical Sites'=> ['Churches', 'Ruins', 'Forts', 'Heritage Towns'],
        'Shopping'        => ['Malls', 'Night Markets', 'Pasalubong', 'Thrift Shops'],
        'Museums'         => ['Art', 'History', 'Science', 'Culture'],
        'Nightlife'       => ['Bars', 'Clubs', 'Live Music', 'Night Markets'],
        'Relaxation'      => ['Spa', 'Beach Resort', 'Hot Springs', 'Wellness'],
    ];

    public const ICONS = [
        'Beach'           => 'fa-umbrella-beach',
        'Nature'          => 'fa-leaf',
        'Food Trip'       => 'fa-utensils',
        'Adventure'       => 'fa-person-hiking',
        'Historical Sites'=> 'fa-landmark',
        'Shopping'        => 'fa-bag-shopping',
        'Museums'         => 'fa-building-columns',
        'Nightlife'       => 'fa-moon',
        'Relaxation'      => 'fa-spa',
    ];

    public const SUGGESTED_CITIES = ['City of Manila', 'Cebu City', 'Davao City'];

    public function mount(): void
    {
        $profile = auth()->user()->userProfile;
        if ($profile) {
            $city = $profile->home_city ?? '';
            $this->homeCity = is_numeric(preg_replace('/[\s,₱]/', '', $city)) ? '' : $city;
            $this->dailyBudget         = $profile->daily_budget ?? 0;
            $this->dailyBudgetDisplay  = $this->dailyBudget ? number_format($this->dailyBudget) : '';
            $this->selectedInterests   = $profile->interests     ?? [];
            $this->selectedSubInterests= $profile->sub_interests ?? [];
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $city = trim($this->homeCity);
            if (empty($city)) {
                $this->addError('homeCity', 'Please enter your home city.');
                return;
            }
            if (is_numeric(preg_replace('/[\s,₱]/', '', $city))) {
                $this->addError('homeCity', 'Please enter a city name (e.g. "Manila"), not a number.');
                return;
            }
        }
        $this->resetErrorBag();
        $this->step++;
    }

    public function prevStep(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function toggleInterest(string $interest): void
    {
        if (in_array($interest, $this->selectedInterests)) {
            $this->selectedInterests = array_values(array_filter(
                $this->selectedInterests, fn($i) => $i !== $interest
            ));
            // remove associated sub-interests
            $subs = self::INTERESTS[$interest] ?? [];
            $this->selectedSubInterests = array_values(array_filter(
                $this->selectedSubInterests, fn($s) => !in_array($s, $subs)
            ));
        } else {
            $this->selectedInterests[] = $interest;
        }
    }

    public function toggleSubInterest(string $sub): void
    {
        if (in_array($sub, $this->selectedSubInterests)) {
            $this->selectedSubInterests = array_values(array_filter(
                $this->selectedSubInterests, fn($s) => $s !== $sub
            ));
        } else {
            $this->selectedSubInterests[] = $sub;
        }
    }

    public function confirmProfile(): void
    {
        UserProfile::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'home_city'      => $this->homeCity,
                'daily_budget'   => $this->dailyBudget,
                'interests'      => $this->selectedInterests,
                'sub_interests'  => $this->selectedSubInterests,
            ]
        );
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.traveler.profile-builder', [
            'interests' => self::INTERESTS,
            'icons'     => self::ICONS,
            'suggested' => self::SUGGESTED_CITIES,
        ])->layout('layouts.app');
    }
}
