<?php

namespace Modules\Admin\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\app\Models\Admin;
use Modules\Admin\app\Repositories\AdminRepositoryInterface;
use Modules\Core\app\Http\Controllers\Controller;

class AdminController extends Controller
{
    private AdminRepositoryInterface $adminRepository;

    public function __construct(AdminRepositoryInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    // Add CRUD methods here
}
