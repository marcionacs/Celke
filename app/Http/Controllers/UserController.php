<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Listar os usuários
    public function index()
    {
       
        // Recuperar os registros do banco de dados
        $users = User::get();

        // Carregar a VIEW
        return view('users.index', ['users' => $users]);
    }

    // Importar os dados do Excel
    public function import(Request $request)
    {
        // Validar o arquivo
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ],[
            'file.required' => 'O campo arquivo é obrigatório.',
            'file.mimes' => 'Arquivo inválido, necessário enviar arquivo CSV.',
            'file.max' => 'Tamanho do arquivo execede :max Mb.'
        ]);

        // Criar o array com as colunas no banco de dados
        $headers = ['name', 'email', 'password'];

        // Receber o arquivo, ler os dados e converter a string em array
        $dataFile = array_map('str_getcsv', file($request->file('file')));

        // Criar a variável para receber a quantidade de registros cadastrados
        $numberRegisteredRecords = 0;

        // Criar a variável que recebe os e-mail que estão cadastrado no banco de dados
        $emailAlreadyRegistered = false;

        // Percorrer as linhas do arquivo CSV
        foreach ($dataFile as $keyData => $row) {
            
            // Converter a linha em array
            $values = explode(';', $row[0]);
            
            // Percorrer as colunas do cabeçalho
            foreach($headers as $key => $header) {

                // Atribuir o valor ao elemento do array
                $arrayValues[$keyData][$header] = $values[$key];

                // verificar se a coluna é e-mail
                if($header == "email"){

                    // Verificar se o e-mail já está cadastrado no banco de dados
                    if(User::where('email', $arrayValues[$keyData]['email'])->first()){

                        // Atribuir o e-mail na lista de e-mails já cadastrados
                        $emailAlreadyRegistered .= $arrayValues[$keyData]['email'] . ", ";
                    }
                }

                // verificar se a coluna é senha
                if($header == "password"){

                    // Criptografar a senha
                    // $arrayValues[$keyData][$header] = Hash::make($arrayValues[$keyData]['password'], ['rounds' => 12]);

                    // Atribuir a senha ao elemento do array, Gerar uma senha aleatória com 7 caracteres
                    $arrayValues[$keyData][$header] = Hash::make(Str::random(7), ['rounds' => 12]);
                }
            }

            // Incrementar mais um registro na quantidade de registros que serão cadastrados
            $numberRegisteredRecords++;
        }

        // Verificar se existe e-mail já cadastrado, retorna erro e não cadastra no banco de dados
        if($emailAlreadyRegistered){

            // Redirecionar o usuário para a página anterior e enviar a mensagem de erro
            return back()->with('error', 'Dados não importados. Existem e-mails já cadastrados.:<br> ' . $emailAlreadyRegistered);

        }

        // Cadastrar registros no banco de dados
        User::insert($arrayValues);

        // Redirecionar o usuário para a página anterior e enviar a mensagem de sucesso
        return back()->with('success', 'Dados importados com sucesso. <br>Quantidade: ' . $numberRegisteredRecords);
    }
}
