# PIX acadêmico

**Formatos de chave de teste:** a aplicação aceita `dev_` e `abc_dev_`. Copie a chave completa, sem remover ou alterar o prefixo. O prefixo sozinho não libera uma cobrança: a resposta da criação ainda precisa conter `devMode: true`. Chaves reais continuam bloqueadas.

Os botões “Comprar agora” do Pro e do Equipe abrem o componente de pagamento sobre a landing page, com fundo em blur, sem recarregar a página. Os valores iniciais são R$ 27,90/mês ou R$ 238,80/ano para Pro (equivalente a R$ 19,90/mês no anual), e R$ 49,90/mês ou R$ 499,00/ano para Equipe. O checkout usa os valores do catálogo no servidor. Não há cartão nem renovação automática.

## Apresentação sem serviço externo (padrão)

1. Execute `php artisan migrate`, `php artisan db:seed --class=PlanSeeder` e `npm run build`. O seeder pode ser repetido: cadastra apenas planos ausentes e preserva os existentes.
2. Mantenha `APP_ENV=local` e `PIX_DRIVER=local` no `.env`.
3. Clique em “Comprar agora” ou acesse `/assinar/pro` ou `/assinar/equipe`. Para visitantes sem sessão, o pagamento abre e em seguida apresenta o mesmo modal de login do botão “Entre” do header. Quem ainda não tem conta pode escolher “Criar conta” no login. Após autenticar e confirmar o e-mail, o fluxo retorna ao pagamento com o plano e o período preservados, inclusive após atualizar a aba. Gere o QR Code e clique em “Simular pagamento”.

O modal compartilha a identidade visual da autenticação: fontes locais, logo, cartão branco arredondado e botões vinho. O período é alterado sem navegação; os valores são obtidos do servidor por `/checkout/options/pro`. A URL acompanha o modal, que pode ser fechado pelo X, Escape ou clique fora. O pedido pendente é restaurado ao reabrir ou recarregar. Login comum continua direcionando ao sistema; o login iniciado durante a compra retorna ao modal. Gerar ou confirmar pagamento continua exigindo sessão e e-mail confirmado. Se o catálogo estiver sem o plano Pro, o modal mostra uma mensagem de indisponibilidade em vez de uma página 404.

O QR é gerado no navegador, sem serviços externos. Contém uma referência demonstrativa, **não um PIX bancário**. Não utilize aplicativo de banco. O servidor persiste o pedido, verifica o dono, registra a confirmação e ativa o plano. Repetir a confirmação do mesmo pedido não duplica a assinatura. A consulta reaproveita o pedido pendente; a aba pode ser recarregada. O código expira após 15 minutos.

## AbacatePay em Dev mode

Crie uma conta e uma chave de desenvolvimento no painel da AbacatePay. Guarde apenas no `.env`, nunca no JavaScript ou no Git:

```dotenv
APP_ENV=local
PIX_DRIVER=abacate
ABACATEPAY_DEV_KEY=dev_sua_chave_de_teste
```

Depois execute `php artisan config:clear`. A chave precisa das permissões indicadas nos endpoints de criar, consultar e simular checkout transparente (na referência de criação, `TRANSPARENT:CREATE`). Não envie a chave pelo chat.

O backend usa a API v2:

- `POST /transparents/create` com `method: PIX` e `data.amount` em centavos.
- `GET /transparents/check?id=...` para confirmar o status.
- `POST /transparents/simulate-payment?id=...` para simular o pagamento.

A criação verifica `devMode: true` e o valor recebido. Credenciais sem prefixo `dev_` são recusadas antes da requisição. O código fornecido pela API é convertido em QR localmente. O botão de simular chama o provedor, e a assinatura só é liberada após consulta server-side retornar `PAID`. O navegador consulta a cada 6 segundos enquanto a aba está visível. A integração externa precisa ser validada com uma chave Dev real; testes automatizados usam respostas HTTP simuladas.

## Demonstração no site hospedado

Mantenha `APP_ENV=production`. Na Vercel, configure `PIX_DEMO_ENABLED=true`, `PIX_DRIVER=abacate` e `ABACATEPAY_DEV_KEY` com uma chave `dev_` da AbacatePay. Faça um novo deploy para aplicar as variáveis. As mesmas regras de autenticação, confirmação de e-mail, propriedade do pedido, validade e ativação sem duplicação continuam valendo. O visitante pode simular a compra de Pro ou Equipe e acessar os recursos correspondentes. A demonstração não concede acesso administrativo.

O padrão continua bloqueado na hospedagem. Mesmo habilitada, a demonstração recusa driver local e chave de cobrança real. A criação ainda exige `devMode: true`; o botão de simulação não ativa o plano sem confirmação server-side do provedor.

Não há webhook nesta etapa: o status é consultado enquanto o checkout está aberto. Para cobranças reais será necessário outro trabalho: webhook autenticado e idempotente, conciliação com a aba fechada, estorno e tratamento de renovação/expiração. Este projeto continua sem cobranças reais.

Referências: https://docs.abacatepay.com/pages/devmode , https://docs.abacatepay.com/pages/transparents/create , https://docs.abacatepay.com/pages/transparents/check , https://docs.abacatepay.com/pages/transparents/simulate-payment .
