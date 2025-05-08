<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @var User */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'password' => Hash::make('oldPassword123')
        ]);
        $this->actingAs($this->user);
    }

    /**
     * Test ID: TC_34
     * Description: Verify settings page loads with profile and password forms
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Visit settings page
     * Test Data: None
     */
    public function test_settings_page_loads()
    {
        $response = $this->get('/settings');
        
        $response->assertStatus(200);
        $response->assertSee('Profile Settings');
        $response->assertSee('Change Password');
        $response->assertSee('Current Password');
        $response->assertSee('New Password');
    }

    /**
     * Test ID: TC_35
     * Description: Verify profile picture upload with valid image
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Upload valid image file
     *  2. Submit form
     * Test Data: Valid JPG image
     */
    public function test_profile_picture_upload_valid_image()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('profile.jpg', 200, 200);
        
        $response = $this->post('/settings/profile/update', [
            'profile_picture' => $file
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verify the file was stored
        Storage::disk('public')->assertExists('profile_pictures/' . $file->hashName());
    }

    /**
     * Test ID: TC_36
     * Description: Verify profile picture upload with invalid file
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Upload non-image file
     *  2. Submit form
     * Test Data: PDF file
     */
    public function test_profile_picture_upload_invalid_file()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $response = $this->post('/settings/profile/update', [
            'profile_picture' => $file
        ]);
        
        $response->assertSessionHasErrors('profile_picture');
        Storage::disk('public')->assertMissing('profile_pictures/' . $file->hashName());
    }

    /**
     * Test ID: TC_37
     * Description: Verify successful password change
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Enter correct current password
     *  2. Enter valid new password
     *  3. Confirm new password
     *  4. Submit form
     * Test Data: 
     *  Current: oldPassword123
     *  New: newSecurePassword123
     *  Confirm: newSecurePassword123
     */
    public function test_successful_password_change()
    {
        $response = $this->post('/settings/password/update', [
            'current_password' => 'oldPassword123',
            'new_password' => 'newSecurePassword123',
            'new_password_confirmation' => 'newSecurePassword123'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verify password was actually changed
        $this->assertTrue(
            Hash::check('newSecurePassword123', $this->user->fresh()->password)
        );
    }

    /**
     * Test ID: TC_38
     * Description: Verify password change fails with wrong current password
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Enter incorrect current password
     *  2. Enter valid new password
     *  3. Submit form
     * Test Data: 
     *  Current: wrongPassword
     *  New: newSecurePassword123
     *  Confirm: newSecurePassword123
     */
    public function test_password_change_wrong_current_password()
    {
        $response = $this->post('/settings/password/update', [
            'current_password' => 'wrongPassword',
            'new_password' => 'newSecurePassword123',
            'new_password_confirmation' => 'newSecurePassword123'
        ]);
        
        $response->assertSessionHasErrors('current_password');
        
        // Verify password was NOT changed
        $this->assertFalse(
            Hash::check('newSecurePassword123', $this->user->fresh()->password)
        );
    }

    /**
     * Test ID: TC_39
     * Description: Verify password change fails with weak password
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Enter correct current password
     *  2. Enter weak new password
     *  3. Submit form
     * Test Data: 
     *  Current: oldPassword123
     *  New: weak
     *  Confirm: weak
     */
    public function test_password_change_weak_password()
    {
        $response = $this->post('/settings/password/update', [
            'current_password' => 'oldPassword123',
            'new_password' => 'weak',
            'new_password_confirmation' => 'weak'
        ]);
        
        $response->assertSessionHasErrors('new_password');
        
        // Verify password was NOT changed
        $this->assertFalse(
            Hash::check('weak', $this->user->fresh()->password)
        );
    }

    /**
     * Test ID: TC_40
     * Description: Verify password change fails with mismatched passwords
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Enter correct current password
     *  2. Enter new password
     *  3. Enter different confirm password
     *  4. Submit form
     * Test Data: 
     *  Current: oldPassword123
     *  New: newSecurePassword123
     *  Confirm: differentPassword123
     */
    public function test_password_change_mismatched_passwords()
    {
        $response = $this->post('/settings/password/update', [
            'current_password' => 'oldPassword123',
            'new_password' => 'newSecurePassword123',
            'new_password_confirmation' => 'differentPassword123'
        ]);
        
        $response->assertSessionHasErrors('new_password');
        
        // Verify password was NOT changed
        $this->assertFalse(
            Hash::check('newSecurePassword123', $this->user->fresh()->password)
        );
    }

    /**
     * Test ID: TC_41
     * Description: Verify password strength meter shows strength levels
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Visit settings page
     *  2. Type in password field
     * Test Data: Various password strengths
     */
    public function test_password_strength_meter()
    {
        $response = $this->get('/settings');
        
        $response->assertStatus(200);
        $response->assertSee('id="password-strength-meter"', false);
        $response->assertSee('Password strength', false);
    }

    /**
     * Test ID: TC_42
     * Description: Verify password toggle visibility works
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Visit settings page
     *  2. Click password toggle button
     * Test Data: None
     */
    public function test_password_toggle_visibility()
    {
        $response = $this->get('/settings');
        
        $response->assertStatus(200);
        $response->assertSee('id="toggle-password"', false);
        $response->assertSee('id="toggle-confirm-password"', false);
    }

    /**
     * Test ID: TC_43
     * Description: Verify profile picture preview works
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Visit settings page
     *  2. Select image file
     * Test Data: Valid JPG image
     */
    public function test_profile_picture_preview()
    {
        $response = $this->get('/settings');
        
        $response->assertStatus(200);
        $response->assertSee('id="profilePicturePreview"', false);
        $response->assertSee('Tap to change profile picture', false);
    }

    /**
     * Test ID: TC_44
     * Description: Verify unauthorized access to settings
     * Pre-Condition: User not logged in
     * Steps: 
     *  1. Logout
     *  2. Visit settings page
     * Test Data: None
     */
    public function test_unauthorized_access_to_settings()
    {
        $this->post('/logout');
        
        $response = $this->get('/settings');
        $response->assertRedirect('/login');
    }

    /**
     * Test ID: TC_45
     * Description: Verify toast notifications display
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Visit settings page
     * Test Data: None
     */
    public function test_toast_notifications_display()
    {
        $response = $this->get('/settings');
        
        $response->assertStatus(200);
        $response->assertSee('id="toastContainer"', false);
    }

    /**
     * Test ID: TC_46
     * Description: Verify confirmation dialog appears
     * Pre-Condition: User logged in
     * Steps: 
     *  1. Visit settings page
     * Test Data: None
     */
    public function test_confirmation_dialog_exists()
    {
        $response = $this->get('/settings');
        
        $response->assertStatus(200);
        $response->assertSee('id="confirmationDialog"', false);
        $response->assertSee('Confirm Action', false);
    }
}