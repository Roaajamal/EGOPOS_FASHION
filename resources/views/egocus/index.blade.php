<!DOCTYPE html>
<html>
<head>
    <title>EGOCUS - Users</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 20px; }
        table { border-collapse: collapse; width: 100%; background-color: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #3CB371; color: white; }
        tr:hover { background-color: #f1f1f1; }
        a.btn-edit {
            background-color: #2E8B57; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px;
        }
        a.btn-edit:hover { background-color: #3CB371; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>All Users</h1>
    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif
    <table>
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Created At</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        @foreach($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->first_name }}</td>
            <td>{{ $user->last_name }}</td>
            <td>{{ $user->username }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->created_at }}</td>
            <td class="status-{{ $user->status }}">{{ ucfirst($user->status) }}</td>
            <td><a href="{{ route('egocus.edit', $user->id) }}" class="btn-edit">Edit</a></td>
        </tr>
        @endforeach
    </table>
</body>
</html>
