<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 20px; }
        form { background-color: white; padding: 20px; max-width: 500px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px;}
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; }
        button { margin-top: 15px; background-color: #2E8B57; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #3CB371; }
        a { display: inline-block; margin-top: 10px; text-decoration: none; color: #2E8B57; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Edit User - {{ $user->username }}</h1>
    <form method="POST" action="{{ route('egocus.update', $user->id) }}">
        @csrf
        <label>First Name</label>
        <input type="text" name="first_name" value="{{ $user->first_name }}" required>

        <label>Last Name</label>
        <input type="text" name="last_name" value="{{ $user->last_name }}">

        <label>Username</label>
        <input type="text" name="username" value="{{ $user->username }}" required>

        <label>Email</label>
        <input type="email" name="email" value="{{ $user->email }}">

        <label>Status</label>
        <select name="status">
            <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $user->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="terminated" {{ $user->status == 'terminated' ? 'selected' : '' }}>Terminated</option>
        </select>

        <button type="submit">Update User</button>
        <a href="{{ route('egocus.index') }}">Back to List</a>
    </form>
</body>
</html>
