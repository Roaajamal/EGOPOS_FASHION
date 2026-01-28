<!DOCTYPE html>
<html>
<head>
    <title>Add New User - EGOCUS</title>
</head>
<body>
    <h1>Add New User</h1>
    <form method="POST" action="{{ route('egocus.store') }}">
        @csrf
        <label>First Name:</label>
        <input type="text" name="first_name" required><br><br>

        <label>Last Name:</label>
        <input type="text" name="last_name"><br><br>

        <label>Username:</label>
        <input type="text" name="username"><br><br>

        <label>Email:</label>
        <input type="email" name="email"><br><br>

        <button type="submit">Save</button>
    </form>
    <br>
    <a href="{{ route('egocus.index') }}">Back to Users List</a>
</body>
</html>
