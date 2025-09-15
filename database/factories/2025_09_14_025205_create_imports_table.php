
<?php
// database/migrations/2024_01_01_000000_create_imports_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
              // table cible
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer'); // classe Importer
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('options')->nullable();
            $table->integer('user_id')->constrained(table: 'main_users')->cascadeOnDelete();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Schema::create('import_failures', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
        //     $table->unsignedInteger('row');
        //     $table->json('errors')->nullable(); // messages de validation
        //     $table->json('data')->nullable();   // données de la ligne
        //     $table->timestamps();
        // });
    }

    public function down(): void
    {
        // Schema::dropIfExists('import_failures');
        Schema::dropIfExists('imports');
    }
};
