<?php

namespace App\Policies;

class TestimonialPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage testimonials';
    }
}
