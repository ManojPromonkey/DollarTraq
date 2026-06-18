<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Connect List</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        table th {
            background: #f5f5f5;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>
<body>

<h2>Connect List</h2>

<table>
    <thead>
        <tr>
            <th>Carrier</th>
            <th>Contact</th>
            <th>DOT</th>
            <th>MC</th>
            <th>Authority</th>
        </tr>
    </thead>
    <tbody>
        @foreach($connect_list as $list)
            <tr>
                <td>{{ $list->carrier?->dba_name }}</td>
                <td>{{ $list->carrier?->legal_name }}</td>
                <td>{{ $list->carrier?->dot_number }}</td>
                <td>{{ $list->carrier?->MC }}</td>
                <td>{{ $list->didit_status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>