<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stored notifications pointed at cases by numeric id (and older ones also
     * kept a numeric URL). Replace both with the case's uuid so no sequential
     * case numbers are left in the notifications table.
     */
    public function up(): void
    {
        $this->rewriteNotifications(function (array $data): ?array {
            if (! isset($data['report_id'])) {
                return null;
            }

            $uuid = DB::table('reports')->where('id', $data['report_id'])->value('uuid');
            unset($data['report_id'], $data['url']);

            return $uuid === null ? $data : ['report_uuid' => $uuid, ...$data];
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->rewriteNotifications(function (array $data): ?array {
            if (! isset($data['report_uuid'])) {
                return null;
            }

            $id = DB::table('reports')->where('uuid', $data['report_uuid'])->value('id');
            unset($data['report_uuid']);

            return $id === null ? $data : ['report_id' => $id, ...$data];
        });
    }

    /**
     * Apply a change to each notification's data; the callback returns null to leave it alone.
     *
     * @param  callable(array<string, mixed>): ?array<string, mixed>  $rewrite
     */
    private function rewriteNotifications(callable $rewrite): void
    {
        DB::table('notifications')->orderBy('id')->chunk(200, function ($notifications) use ($rewrite): void {
            foreach ($notifications as $notification) {
                $data = json_decode($notification->data, true);

                if (! is_array($data)) {
                    continue;
                }

                $rewritten = $rewrite($data);

                if ($rewritten !== null) {
                    DB::table('notifications')->where('id', $notification->id)->update(['data' => json_encode($rewritten)]);
                }
            }
        });
    }
};
