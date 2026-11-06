<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Users of the Grocery Restable user login demo (POST /api/auth/login, /api/auth/google).
 */
class CreateUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 191],
            'username' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            // password_hash() formats (argon2id by default): 255 leaves room for stronger parameters
            'password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'avatar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            // Google "sub" of linked accounts
            'google_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('google_id');
        $this->forge->createTable('users', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
