<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\TestItems;
use App\Http\Controllers\Controller;


class TestItemsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TestItems::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return TestItems::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $TestItem = TestItems::findOrFail($id);

        return $TestItem->delete();

        return $TestItem;
    }
}
