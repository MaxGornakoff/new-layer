<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = Client::query()
            ->with('user:id,name,email')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $client = Client::create($this->validatedData($request));

        return response()->json(['data' => $client], 201);
    }

    public function show(Client $client): JsonResponse
    {
        $client->load(['user:id,name,email', 'orders']);

        return response()->json(['data' => $client]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $client->update($this->validatedData($request));

        return response()->json(['data' => $client->fresh()]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
