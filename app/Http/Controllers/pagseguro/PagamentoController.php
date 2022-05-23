<?php

namespace App\Http\Controllers\pagseguro;

use App\Http\Controllers\Controller;
use Faker\Core\Uuid;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;
use PagSeguro\Configuration\Configure;
use PagSeguro\Domains\Requests\DirectPayment\CreditCard;
use PhpParser\Node\Expr\Array_;

class PagamentoController extends Controller
{
    //
    private $_configs;

    public function __construct(){
        $this->_configs= new Configure();
        $this->_configs->setCharset("UTF-8");
        $this->_configs->setAccountCredentials(env('PAGSEGURO_EMAIL'), env('PAGSEGURO_TOKEN'));
        $this->_configs->setEnvironment(env('PAGSEGURO_AMBIENTE'));
        $this->_configs->setLog(true,storage_path('logs/pagseguro_'.date('ymd').'log'));
    }

    public function getCredential(){
        return $this->_configs->getAccountCredentials();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pagar(){
        $sessionCode = \PagSeguro\Services\Session::create(
            $this->getCredential()
        );
        $IDSession = $sessionCode->getResult();


//        if(env('PAGSEGURO_AMBIENTE') != 'sandbox'){
//            $client = new \GuzzleHttp\Client();
//            $response = $client->request('POST', env('PAGSEGURO_API').'/public-keys', [
//                'headers' => [
//                    'Accept' => 'application/json',
//                    'Authorization' => 'Bearer '.env('PAGSEGURO_TOKEN'),
//                    'Content-type' => 'application/json',
//                ],
//            ]);
//            $pub_key = $response->getBody();
//        }else{
//            $pub_key = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr+ZqgD892U9/HXsa7XqBZUayPquAfh9xx4iwUbTSUAvTlmiXFQNTp0Bvt/5vK2FhMj39qSv1zi2OuBjvW38q1E374nzx6NNBL5JosV0+SDINTlCG0cmigHuBOyWzYmjgca+mtQu4WczCaApNaSuVqgb8u7Bd9GCOL4YJotvV5+81frlSwQXralhwRzGhj/A57CGPgGKiuPT+AOGmykIGEZsSD9RKkyoKIoc0OS8CPIzdBOtTQCIwrLn2FxI83Clcg55W8gkFSOS6rWNbG5qFZWMll6yl02HtunalHmUlRUL66YeGXdMDC2PuRcmZbGO5a/2tbVppW6mfSWG3NPRpgwIDAQAB";
//        }

        return view('pagseguro.pagamento')->with(compact('IDSession'));

    }

    public function efetuaPagamento(Request $request){


        $product1 = array(
            "id"=> 5,
            "quantidade"=> 1,
            "name"=>"Produto de teste",
            "valor"=> 200.50
        );
        $product2 = array(
            "id"=> 6,
            "quantidade"=> 1,
            "name"=>"Produto de teste 2",
            "valor"=> 200.50
        );
        $products = [];
        array_push($products, $product1, $product2);

        $user = array(
            "name" => "nome do usuario",
            "email"=>"nometeste@sandbox.pagseguro.com.br",
            "ddd" => "16",
            "phone" => "999999999",
            "birth"=>"01/01/1980",
            "cpf"=>"33333333333"
        );

        $cardtoken = $request->get('cardToken');
        $hashseller = $request->get('hashseller');
        $nparcela = $request->get('nParcela');
        $totalPagar = $request->get('totalPagar');
        $totalParcela = $request->get('totalParcel');


        $credCard = new CreditCard();
        $credCard->setReference('PED_'. 'iddopedidos');
        $credCard->setCurrency('BRL');

        foreach ($products as $p){
            $credCard->addItems()->withParameters(
                $p["id"],
                $p["name"],
                $p["quantidade"],
                number_format($p["valor"], 2, ".", "")
            );
        }


        $credCard->setSender()->setName($user["name"]);// nome completo
        $credCard->setSender()->setEmail($user["email"]);
        $credCard->setSender()->setHash($hashseller);
        $credCard->setSender()->setPhone()->withParameters(16, 999999999);
        $credCard->setSender()->setDocument()->withParameters("CPF", $user["cpf"]);

        $credCard->setShipping()->setAddress()->withParameters(
            'Rua sei la o que',
            '12345',
            'Centro',
            '14140000',
            'Cravinhos',
            'SP',
            'BRA',
            'Apt 200'
        );
        $credCard->setBilling()->setAddress()->withParameters(
            'Rua sei la o que',
            '12345',
            'Centro',
            '14140000',
            'Cravinhos',
            'SP',
            'BRA',
            'Apt 200'
        );
        $credCard->setToken($cardtoken);

        $credCard->setInstallment()->withParameters(
            $nparcela,
            number_format($totalParcela, 2, ".", "")
        );

        $credCard->setHolder()->setName($user['name']);
        $credCard->setHolder()->setDocument()->withParameters("CPF", $user["cpf"]);
        $credCard->setHolder()->setBirthDate($user["birth"]);
        $credCard->setHolder()->setPhone()->withParameters(16, 999999999);

        $credCard->setMode("DEFAULT");

        $request = $credCard->register($this->getCredential());

        dd($request);

        return $request;

    }

}
