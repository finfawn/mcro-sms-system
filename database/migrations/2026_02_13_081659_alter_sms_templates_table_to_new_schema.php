<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_templates', 'service_type')) {
                $table->string('service_type')->after('id');
            }
            if (!Schema::hasColumn('sms_templates', 'event_key')) {
                $table->string('event_key')->after('service_type');
            }
            if (!Schema::hasColumn('sms_templates', 'template_body')) {
                $table->text('template_body')->after('event_key');
            }
            if (!Schema::hasColumn('sms_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('template_body');
            }
            if (Schema::hasColumn('sms_templates', 'code')) {
                $table->dropUnique('sms_templates_code_unique');
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('sms_templates', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('sms_templates', 'body')) {
                $table->dropColumn('body');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
                if (Schema::hasColumn('sms_templates', 'service_type')) {
                    $table->dropColumn('service_type');
                }
                if (Schema::hasColumn('sms_templates', 'event_key')) {
                    $table->dropColumn('event_key');
                }
                if (Schema::hasColumn('sms_templates', 'template_body')) {
                    $table->dropColumn('template_body');
                }
                if (Schema::hasColumn('sms_templates', 'is_active')) {
                    $table->dropColumn('is_active');
                }
        });
    }
};
