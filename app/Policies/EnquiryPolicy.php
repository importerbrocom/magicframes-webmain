<?php

namespace App\Policies;

class EnquiryPolicy extends PermissionPolicy
{
    protected function permission(): string
    {
        return 'manage enquiries';
    }
}
