@extends('layouts.main')

@section('content')
  <div class="welcome-card">
    <h1>Analysis</h1>
    <p>View your Analysis effectively with categorized insights.</p>
  </div>

  <div class="stats">
    <div class="card">
      <i class="fas fa-receipt fa-2x" style="color:#4b8dbf;"></i>
      <h3>TOTAL EXPENSES</h3>
      <p>₱15,100.00</p>
      <small>Last 30 Days</small>
    </div>
    <div class="card">
      <i class="fas fa-calendar-alt fa-2x" style="color:#4b8dbf;"></i>
      <h3>UPCOMING BILLS</h3>
      <p>₱5,500.00</p>
      <small>Next Week</small>
    </div>
  </div>
@endsection
