<table>
    <thead>
        <tr>
            <th>Borrower Name</th>
            <th>Equipment Type</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Condition</th>
            <th>Status</th>
            <th>Date Borrowed</th>
            <th>Date Returned</th>
            <th>Office Name</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $record)
            <tr>
                <td>{{ $record->account->full_name ?? '' }}</td>
                <td>{{ $record->equipment->type ?? '' }}</td>
                <td>{{ $record->equipment->brand ?? '' }}</td>
                <td>{{ $record->equipment->model ?? '' }}</td>
                <td>{{ $record->equipment->condition ?? '' }}</td>
                <td>{{ $record->status }}</td>
                <td>{{ $record->date_borrow }}</td>
                <td>{{ $record->date_return }}</td>
                <td>{{ $record->account->office_name ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
