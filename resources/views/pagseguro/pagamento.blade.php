@extends('layout.layout')

@section('title', 'Pagamento')
@section('description', 'Pagamento')

@section('content')
    <form id="formulario">
        {{route('efetua-pagamento')}}
        @csrf
        <input type="text"   name="hash" id="hashcard" placeholder="hash"/>
        <input type="text"   name="brand" id="brand" placeholder="brand"/>
        <input type="text"   name="totalparcela" id="totparcela" placeholder="taotalparcela"/>

        <input type="text" value="José da silva" name="holder" id="holder" placeholder="Nome no Cartão"/>
        <input type="text" value="4111111111111111" name="cardnumber" id="card_number" placeholder="card"/>
        <input type="text" value="12" name="expMonth" id="expMonth" placeholder="Mes de expiração">
        <input type="text" value="2030" name="expYear" id="expYear" placeholder="Ano de Expiração">
        <input type="text" name="securityCode" id="securityCode" placeholder="Codigo de Segurança">

        <select name="parcelas" id="parcelas">
            <option value="">Selecione o numero de parcelas</option>
        </select>

        <button type="submit"> Enviar </button>
    </form>

@endsection

@section('scripts')
{{--    <script type="text/javascript" src="https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>--}}
    <script type="text/javascript" src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>
    <script src="https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js" type="application/javascript"></script>
    <script>

        const total = 401.00
        const parcelas = 1
        var hash = "";
        var brand = "";
        var installments = [];
        var cardToken = "";

        var hashcard = document.getElementById(`hashcard`);
        const cardnumber = document.getElementById(`card_number`);
        const cvv = document.getElementById(`securityCode`);
        const inputbrand = document.getElementById(`brand`);
        const mesexp = document.getElementById(`expMonth`);
        const anoexp = document.getElementById(`expYear`);
        const inputselect = document.getElementById("parcelas")
        const totparcela = document.getElementById("totparcela")

        cardnumber.addEventListener("blur", function (event){
            getCardBrand()
        })

        cvv.addEventListener("blur", function (event){
            getIstallments()
        })

        inputselect.addEventListener("change", function (event){
            totparcela.value = installments[inputselect.value - 1].totalAmount

            createCardToken()
        })

        function carregarSessao(){
            PagSeguroDirectPayment.setSessionId('{{$IDSession}}');
            //metodos disponiveis
            PagSeguroDirectPayment.getPaymentMethods({
                amount: total,
                success: function(response) {
                    // Retorna os meios de pagamento disponíveis.
                    console.log(response)
                },
                error: function(response) {
                    // Callback para chamadas que falharam.
                    console.log(response)
                },
                complete: function(response) {
                    // Callback para todas chamadas.
                    console.log(response)
                }
            });
        }
        function indentificadordocompradorsessao(){
            PagSeguroDirectPayment.onSenderHashReady(function(response){
                if(response.status == 'error') {
                    console.log(response.message);
                    return false;
                }
                hash = response.senderHash; //Hash estará disponível nesta variável.
                hashcard.value = hash
                console.log(hash)
            });
        }
        function getCardBrand(){
            PagSeguroDirectPayment.getBrand({
                cardBin: cardnumber.value.substr(0, 6),
                success: function(response) {
                    //bandeira encontrada
                    console.log(response)
                    brand = response.brand.name
                    inputbrand.value = brand
                },
                error: function(response) {
                    //tratamento do erro
                    console.log(response)
                },
                complete: function(response) {
                    //tratamento comum para todas chamadas
                    console.log(response)
                }
            });
        }
        function getIstallments(){
            PagSeguroDirectPayment.getInstallments({
                amount: total,
                maxInstallmentNoInterest: parcelas,
                brand: brand,
                success: function(response){
                    // Retorna as opções de parcelamento disponíveis
                    console.log(response)
                    installments = response.installments[brand]
                    popularParcelas()
                },
                error: function(response) {
                    // callback para chamadas que falharam.
                    console.log(response)
                },
                complete: function(response){
                    // Callback para todas chamadas.
                    console.log(response)
                }
            });
        }
        function createCardToken(){
            console.log(cardnumber)
            PagSeguroDirectPayment.createCardToken({
                cardNumber: cardnumber.value, // Número do cartão de crédito
                brand: brand, // Bandeira do cartão
                cvv: cvv.value, // CVV do cartão
                expirationMonth: mesexp.value, // Mês da expiração do cartão
                expirationYear: anoexp.value, // Ano da expiração do cartão, é necessário os 4 dígitos.
                success: function(response) {
                    // Retorna o cartão tokenizado.
                    console.log(response)
                    cardToken = response.card.token
                },
                error: function(response) {
                    // Callback para chamadas que falharam.
                    console.log(response)
                },
                complete: function(response) {
                    // Callback para todas chamadas.
                    console.log(response)
                }
            });
        }

        carregarSessao()
        indentificadordocompradorsessao()
        // indentificadordocompradorsessao()
        // getCardBrand()
        // getIstallments()
        // createCardToken()

        function popularParcelas(){


            installments.forEach((item)=>{
                console.log("rodou")
                const option = new Option(item.quantity+" x "+ item.installmentAmount + "(R$"+item.totalAmount+")",item.quantity)
                inputselect.options[inputselect.options.length] = option;
            })
        }




        const form = document.getElementById("formulario")

        form.addEventListener('submit', sendpost)

        function sendpost(e){
            e.preventDefault();



            var hashseller = hash
            var token = cardToken
            var nParcela = inputselect.value
            var parcelatot = totparcela.value
            var totalPagar = total


            fetch("{{route('efetua-pagamento')}}",
                {
                    method:"POST",
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        _token: "{{ csrf_token() }}",
                        hashseller: hashseller,
                        cardToken: token,
                        nParcela: nParcela,
                        totalParcel: parcelatot,
                        totalPagar: totalPagar,
                    })
                }
            ).then(
                response=>{
                    return response.json();
                }
            )
            console.log("enviou")
        }




        {{--var cardnumber = document.getElementById(`card_number`);--}}
        {{--var hashcard = document.getElementById(`hashcard`);--}}

        {{--cardnumber.addEventListener("blur", function (event){--}}
        {{--    var card = PagSeguro.encryptCard({--}}
        {{--        publicKey: '{{$pub_key}}',--}}
        {{--        holder: "Nome Sobrenome",--}}
        {{--        number: "4242424242424242",--}}
        {{--        expMonth: "12",--}}
        {{--        expYear: "2030",--}}
        {{--        securityCode: "123"--}}
        {{--    });--}}
        {{--    var encrypted = card.encryptedCard;--}}
        {{--    console.log(encrypted)--}}
        {{--    hashcard.value = encrypted--}}
        {{--}, true)--}}

        // var card = PagSeguro.encryptCard({
        //     publicKey: "MINHA_CHAVE_PUBLICA",
        //     holder: "Nome Sobrenome",
        //     number: "4242424242424242",
        //     expMonth: "12",
        //     expYear: "2030",
        //     securityCode: "123"
        // });
        // var encrypted = card.encryptedCard;
    </script>
@endsection


