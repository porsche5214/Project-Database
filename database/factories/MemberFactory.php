<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName() . Str::random(4),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => static::$password ??= 'password123',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '08' . fake()->numerify('########'),
            'role' => 'member',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}
