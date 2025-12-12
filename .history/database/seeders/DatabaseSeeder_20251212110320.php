<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Admin::create([
            'name' => 'Gerente Geral',
            'email' => 'gerente@gerente.com',
            'password' => Hash::make('senha123'),
            'slug' => 'gerente-geral',
            'role' => 'superadmin',
            'bio' => 'Responsável pela gestão completa do blog',
            'email_verified_at' => now()
        ]);

        $autores = [
            ['name' => 'João Silva', 'email' => 'joao@blog.com', 'slug' => 'joao-silva'],
            ['name' => 'Maria Santos', 'email' => 'maria@blog.com', 'slug' => 'maria-santos'],
            ['name' => 'Pedro Costa', 'email' => 'pedro@blog.com', 'slug' => 'pedro-costa']
        ];

        foreach($autores as $autor) {
            Admin::create([
                ...$autor,
                'password' => Hash::make('senha123'),
                'role' => 'author',
                'bio' => 'Autor de notícias especializado',
                'email_verified_at' => now()
            ]);
        }

        Category::create(['name' => 'Esportes', 'slug' => 'esportes']);
        Category::create(['name' => 'Política', 'slug' => 'politica']);
        Category::create(['name' => 'Tecnologia', 'slug' => 'tecnologia']);
        Category::create(['name' => 'Entretenimento', 'slug' => 'entretenimento']);
        Category::create(['name' => 'Saúde', 'slug' => 'saude']);
        Category::create(['name' => 'Economia', 'slug' => 'economia']);
        Category::create(['name' => 'Ciência', 'slug' => 'ciencia']);
    }
}
