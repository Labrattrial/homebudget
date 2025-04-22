@extends('layouts.main')

@section('content')
  <div class="welcome-card">
    <h1>Settings</h1>
    <p>Personalize your account preferences and app settings.</p>
  </div>

  <div class="stats">
    <div class="card">
      <i class="fas fa-user-cog fa-2x" style="color:#4b8dbf;"></i>
      <h3>ACCOUNT SETTINGS</h3>
      <p>Update your profile and preferences.</p>
    </div>
    <div class="card">
      <i class="fas fa-bell fa-2x" style="color:#4b8dbf;"></i>
      <h3>NOTIFICATIONS</h3>
      <p>Control notification settings for reminders and updates.</p>
    </div>
  </div>
@endsection
