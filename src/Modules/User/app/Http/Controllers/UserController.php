<?php

namespace Modules\User\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\app\Models\User;
use Modules\User\app\Repositories\UserRepositoryInterface;
use Modules\Core\app\Http\Controllers\Controller;

class UserController extends Controller
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index(): JsonResponse
    {
        $users = User::all();
        return response()->json($users);
    }

    public function show($id): JsonResponse
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        $user = User::create($data);
        return response()->json($user, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $user->update($request->all());
        return response()->json($user);
    }

    public function destroy($id): JsonResponse
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $user->delete();
        return response()->json(null, 204);
    }
}
