<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParentFcmTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_token_can_be_registered_for_linked_student(): void
    {
        $studentId = $this->student();
        $token = 'parent-token-abcdef';
        $apiToken = $this->apiToken($studentId, 'orang_tua');

        $response = $this->withHeader('Authorization', 'Bearer '.$apiToken)
            ->postJson('/api/fcm/register-parent', [
                'siswa_id' => $studentId,
                'token' => $token,
                'audience' => 'orang_tua',
                'device_name' => 'Android Orang Tua',
            ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('parent_fcm_tokens', [
            'siswa_id' => $studentId,
            'token' => $token,
            'audience' => 'orang_tua',
            'device_name' => 'Android Orang Tua',
            'is_active' => true,
        ]);
    }

    public function test_token_registration_does_not_accept_wrong_role_context(): void
    {
        $studentId = $this->student();
        $apiToken = $this->apiToken($studentId, 'orang_tua');

        $this->withHeader('Authorization', 'Bearer '.$apiToken)
            ->postJson('/api/fcm/register-parent', [
                'siswa_id' => $studentId,
                'token' => 'student-token-abcdef',
                'audience' => 'siswa',
                'device_name' => 'Android',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('parent_fcm_tokens', [
            'token' => 'student-token-abcdef',
        ]);
    }

    public function test_multiple_parent_devices_are_kept_as_separate_tokens(): void
    {
        $studentId = $this->student();
        $apiToken = $this->apiToken($studentId, 'orang_tua');

        foreach (['token-device-one', 'token-device-two'] as $token) {
            $this->withHeader('Authorization', 'Bearer '.$apiToken)
                ->postJson('/api/fcm/register-parent', [
                    'siswa_id' => $studentId,
                    'token' => $token,
                    'audience' => 'orang_tua',
                    'device_name' => 'Android Orang Tua',
                ])
                ->assertOk();
        }

        $this->assertSame(2, DB::table('parent_fcm_tokens')->where('siswa_id', $studentId)->where('audience', 'orang_tua')->count());
    }

    public function test_parent_notification_uses_only_active_parent_tokens_and_deactivates_invalid_token(): void
    {
        $studentId = $this->student();
        DB::table('parent_fcm_tokens')->insert([
            [
                'siswa_id' => $studentId,
                'audience' => 'orang_tua',
                'token' => 'valid-parent-token',
                'device_name' => 'Parent 1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'siswa_id' => $studentId,
                'audience' => 'orang_tua',
                'token' => 'invalid-parent-token',
                'device_name' => 'Parent 2',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'siswa_id' => $studentId,
                'audience' => 'siswa',
                'token' => 'student-token',
                'device_name' => 'Siswa',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'siswa_id' => $studentId,
                'audience' => 'orang_tua',
                'token' => 'inactive-parent-token',
                'device_name' => 'Inactive',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->fakeServiceAccount();
        $sentTokens = [];
        Http::fake([
            'https://oauth2.googleapis.com' => Http::response('', 200, ['Date' => gmdate('D, d M Y H:i:s').' GMT']),
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token'], 200),
            'https://fcm.googleapis.com/*' => function ($request) use (&$sentTokens) {
                $token = $request->data()['message']['token'];
                $sentTokens[] = $token;

                if ($token === 'invalid-parent-token') {
                    return Http::response([
                        'error' => [
                            'status' => 'INVALID_ARGUMENT',
                            'details' => [
                                ['errorCode' => 'INVALID_ARGUMENT'],
                            ],
                        ],
                    ], 400);
                }

                return Http::response(['name' => 'projects/test/messages/123'], 200);
            },
        ]);

        kirimNotifikasiOrangTua($studentId, 'Judul', 'Pesan', ['tipe' => 'test']);

        $this->assertEqualsCanonicalizing(['valid-parent-token', 'invalid-parent-token'], $sentTokens);
        $this->assertDatabaseHas('parent_fcm_tokens', [
            'token' => 'invalid-parent-token',
            'is_active' => false,
            'last_error_code' => 'INVALID_ARGUMENT',
        ]);
    }

    private function student(): int
    {
        return DB::table('users')->insertGetId([
            'nama' => 'Siswa Test',
            'nis' => '1001',
            'username' => 'siswa1001',
            'password' => 'secret',
            'role' => 'siswa',
            'no_ortu' => '08123456789',
            'nama_ortu' => 'Orang Tua Test',
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function apiToken(int $studentId, string $roleContext): string
    {
        $token = 'plain-token-'.$studentId.'-'.$roleContext;
        DB::table('api_access_tokens')->insert([
            'user_id' => $studentId,
            'role_context' => $roleContext,
            'token_hash' => hash('sha256', $token),
            'device_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $token;
    }

    private function fakeServiceAccount(): void
    {
        Cache::put('fcm_http_v1_access_token', 'test-access-token', 3300);

        Storage::fake('local');
        Storage::disk('local')->put('firebase-service-account-test.json', json_encode([
            'project_id' => 'baabsensi-test',
            'client_email' => 'firebase-adminsdk-test@baabsensi-test.iam.gserviceaccount.com',
            'private_key' => 'test-private-key',
        ]));

        config(['services.fcm.service_account_path' => Storage::disk('local')->path('firebase-service-account-test.json')]);
    }
}
