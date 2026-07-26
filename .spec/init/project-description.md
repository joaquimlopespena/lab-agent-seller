
# Lab Agent Seller — Project Description

## Overview

O **Lab Agent Seller** é um projeto educacional para estudo e experimentação prática de Inteligência Artificial por meio da construção de uma arquitetura de **Agent as a Service (AAS)**. Como produto, ele será uma plataforma multi-tenant na qual empresas disponibilizam um agente de IA para conversar com seus clientes em um contexto de atendimento e vendas.

No MVP, cada empresa terá um administrador principal, conectará sua conta do Pipedrive via OAuth e executará uma sincronização inicial, prioritariamente de leitura, para que a plataforma compreenda a estrutura e os dados do CRM. A integração e os dados sincronizados serão mantidos separados da camada de contexto do agente, permitindo que o agente evolua sem acoplamento à sincronização.

O cliente final acessará a plataforma por link mágico enviado somente quando seu e-mail estiver associado a uma pessoa sincronizada de ao menos uma empresa. Após a autenticação, ele iniciará uma conversa persistente no tenant selecionado. O primeiro agente será deliberadamente simples: **um único agente, com um único System Prompt global versionado no código**, sem contexto específico por empresa, tools, actions no CRM ou RAG.

O MVP não incluirá cobrança, assinaturas, planos, limites comerciais, métricas avançadas, múltiplos administradores, papéis granulares, múltiplos agentes, personalização de prompts ou sincronização contínua complexa. A arquitetura deverá preservar o isolamento entre tenants e evitar impedimentos desnecessários para essas evoluções futuras.

### Key Concepts

- **Agent as a Service (AAS):** arquitetura estudada e construída pelo projeto para disponibilizar agentes de IA como serviço a empresas.
- **Empresa (Tenant):** entidade central de isolamento; usuários administrativos, conexões e dados do CRM, clientes, conversas, mensagens e configurações pertencem a exatamente uma empresa e não podem ser acessados por outra.
- **Administrador principal:** único usuário administrativo de uma empresa no MVP, responsável por configurar a empresa, conectar o CRM, acompanhar a sincronização e visualizar as conversas do próprio tenant.
- **Conexão CRM:** autorização OAuth entre uma empresa e o Pipedrive; no MVP, permite somente a leitura necessária à sincronização, sem alterações no CRM.
- **Sincronização inicial:** importação inicial da estrutura e dos dados relevantes do Pipedrive. Sincronização contínua por webhooks, jobs, filas ou periodicidade fica para evolução futura.
- **Dados sincronizados:** representação local de pipelines, etapas, campos personalizados, pessoas, organizações, negócios, produtos, atividades e usuários do CRM quando disponíveis e relevantes.
- **Contexto do agente:** conjunto de informações efetivamente fornecido ao agente. No MVP, não inclui os dados sincronizados do CRM e permanece separado da integração.
- **Cliente final:** pessoa cujo e-mail existe nos dados sincronizados de ao menos uma empresa e que pode acessar o chat dessa empresa.
- **Link mágico:** credencial de autenticação de uso único, com validade padrão de 15 minutos e duração configurável.
- **Sessão do cliente:** sessão criada após o uso válido do link mágico, com duração padrão de 24 horas e valor configurável.
- **Agente global:** único agente do MVP, com um único System Prompt global da plataforma, versionado no código e sem customização por empresa.
- **Conversa:** histórico persistente entre um cliente e o agente dentro do contexto de uma empresa, com status e timestamps.
- **Mensagem:** registro persistente pertencente a uma conversa, com conteúdo, papel (`user`, `assistant` ou `system`, quando aplicável) e timestamps.

## Tech Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.3 e Laravel 13 |
| IA | Laravel AI SDK 0.10; provedor e modelo ainda não definidos |
| Autenticação | Laravel Fortify 1 |
| Frontend SPA | Inertia.js 3, React 19 e TypeScript 5.7 |
| Rotas tipadas | Laravel Wayfinder 0.1 |
| UI e estilos | Tailwind CSS 4 e componentes Radix UI |
| Banco de dados | PostgreSQL 18 |
| Cache e infraestrutura assíncrona | Redis |
| Build | Vite 8 |
| Testes | Pest 4 e PHPUnit 12 |
| Análise e formatação | Larastan 3, Laravel Pint 1, ESLint 9 e Prettier 3 |
| Ambiente de desenvolvimento | Laravel Sail e Docker Compose |
| Integração externa do MVP | API do Pipedrive via OAuth, em modo de leitura |

## Core Workflows

### 1. Cadastro e configuração da empresa

1. O administrador principal cadastra sua conta e sua empresa na plataforma.
2. A plataforma associa o administrador ao tenant da empresa.
3. O administrador acessa somente as configurações, integrações, dados sincronizados e conversas pertencentes à própria empresa.
4. O MVP mantém um administrador principal por empresa; múltiplos usuários, papéis e permissões granulares ficam fora do escopo inicial.

### 2. Conexão e sincronização inicial do Pipedrive

1. O administrador inicia a conexão com o Pipedrive.
2. A plataforma executa o fluxo OAuth e associa a conexão autorizada exclusivamente ao tenant.
3. Após a autorização, a plataforma consulta a API do Pipedrive sem alterar dados no CRM.
4. A sincronização investiga e recupera, quando disponíveis e relevantes, pipelines, etapas, campos personalizados, pessoas, organizações, negócios, produtos, atividades e usuários do CRM.
5. Os dados são persistidos com associação obrigatória à empresa e ficam visíveis ao administrador do mesmo tenant.
6. Os dados sincronizados permanecem separados do contexto fornecido ao agente e não são utilizados por ele no MVP.
7. Uma estratégia complexa de atualização contínua não faz parte do MVP; a arquitetura apenas deverá admitir futuramente webhooks, jobs, filas e sincronizações periódicas.

### 3. Solicitação e uso do link mágico

1. O cliente final informa seu e-mail na tela de acesso.
2. A plataforma procura o e-mail nas pessoas sincronizadas das empresas cadastradas.
3. Se o e-mail não existir em nenhuma empresa, nenhum link é enviado e a plataforma informa que não encontrou empresa associada ao endereço.
4. Se o e-mail existir em uma ou mais empresas, a plataforma envia um link mágico de uso único, com validade padrão de 15 minutos.
5. Ao acessar um link válido, a plataforma autentica o cliente e cria uma sessão com validade padrão de 24 horas.
6. Links expirados ou já utilizados não autenticam o cliente.
7. As validades do link e da sessão são configuráveis e não ficam dispersas pelo código.

### 4. Seleção da empresa para a conversa

1. Após a autenticação, a plataforma determina todas as empresas às quais o e-mail do cliente está associado.
2. Quando houver somente uma empresa, ela será usada como contexto da conversa.
3. Quando houver mais de uma empresa, a plataforma apresentará as opções e exigirá que o cliente escolha com qual deseja conversar.
4. A escolha estabelece o tenant ativo, e toda conversa ou mensagem criada a partir desse ponto pertence à empresa selecionada.
5. O cliente não obtém acesso a dados ou conversas de empresas fora das associações encontradas para seu e-mail.

### 5. Conversa com o agente global

1. O cliente autenticado inicia ou continua uma conversa no contexto da empresa selecionada.
2. A plataforma persiste a empresa, o cliente, a conversa, seu status e seus timestamps.
3. Cada mensagem é armazenada com seu papel, conteúdo e timestamps.
4. A plataforma envia a entrada ao único agente do MVP, usando o System Prompt global versionado no código.
5. O agente produz uma resposta conversacional, que também é persistida no histórico.
6. O agente não recebe contexto específico da empresa ou dados sincronizados do Pipedrive e não possui tools, actions, RAG ou capacidade de alterar o CRM.
7. As regras de negócio dependem de uma abstração de provedor de IA, permitindo substituir ou adicionar provedores e modelos futuramente.

### 6. Consulta administrativa das conversas

1. O administrador acessa a área de conversas da própria empresa.
2. A plataforma lista somente conversas e clientes associados ao tenant do administrador.
3. O administrador visualiza o histórico persistido, incluindo mensagens, papéis, status e timestamps.
4. Toda consulta aplica isolamento multi-tenant; conversas, clientes ou mensagens de outra empresa nunca são expostos.

## Open Questions

- Qual provedor e qual modelo de IA serão usados inicialmente? A decisão deverá respeitar a abstração de provedor e não acoplar as regras de negócio a uma implementação específica.
