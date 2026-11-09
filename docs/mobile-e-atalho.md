# Mobile e atalho do Escopo Fácil

O site e o sistema possuem ajustes para celular e tablet. Abaixo de 900 px, o sistema usa uma barra superior e menu lateral recolhível. O menu pode ser fechado pelo botão, pelo fundo ou com Escape; o foco permanece dentro dele enquanto estiver aberto. Os temas claro e escuro continuam disponíveis.

Dashboard, projetos e formulários se reorganizam conforme a largura. O Kanban mantém as colunas lado a lado com rolagem horizontal própria. As tabelas administrativas também rolam dentro do painel para preservar a leitura das colunas. O restante da página ocupa a largura da tela.

## Adicionar à tela inicial

O botão aparece no rodapé do site e no card **Baixe nosso app**, na página **Configuração** do sistema. O manifesto em `public/manifest.webmanifest` define nome, cores, ícones e a abertura em `/dashboard`. Quem não estiver autenticado passa pelo login normalmente.

- Quando o navegador disponibiliza `beforeinstallprompt`, o botão abre a confirmação nativa de instalação.
- Sem esse recurso, um diálogo mostra como criar o atalho pelo menu do navegador. No iPhone/iPad, orienta usar Compartilhar → Adicionar à Tela de Início e tentar pelo Safari se necessário.
- Ao abrir em modo instalado, o botão fica oculto.
- Os PNGs em `public/icons/` foram gerados a partir da logo enviada para os ícones, preservada em `brand-mark.png`, incluindo favicons de 16 e 32 px, ícones de 192 e 512 px, ícone maskable e Apple Touch Icon. As versões maskable e Apple possuem margem de segurança para o recorte feito pelo dispositivo.

A instalação depende do navegador, do dispositivo, de HTTPS na hospedagem e da decisão do usuário. Não é um download de APK nem uma publicação em loja. O sistema precisa de internet: esta implementação não adiciona cache offline de telas autenticadas nem de dados de projetos.

## Validação

```sh
php artisan test
node --test tests/JavaScript/*.test.cjs
npm run build
```

Conferir em celular: menu, mudança de tema, dashboard, busca/criação de projetos, rolagem do Kanban, modais, configuração e administração. Testar a instalação real na URL HTTPS; a confirmação nativa não pode ser garantida apenas pela emulação de tela do navegador desktop.
