<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EGOCUSController extends Controller
{
    // عرض جميع المستخدمين
    public function index()
    {
        $users = DB::table('users')->get();
        return view('egocus.index', compact('users'));
    }

    // عرض نموذج التعديل
    public function edit($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        return view('egocus.edit', compact('user'));
    }

    // حفظ التعديل
    public function update(Request $request, $id)
    {
        DB::table('users')->where('id', $id)->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'status' => $request->status,
            'updated_at' => now(),
        ]);

        return redirect()->route('egocus.index')->with('success', 'User updated successfully.');
    }
}
