# Lab Agent Seller — Project Phases

<!-- inputs: project-description.md@sha256:c0b4c85fa4e7 user-stories.md@sha256:cd886a1f9c60 database-schema.md@sha256:71aac04b8757 -->

## Overview

A construção será executada em 11 fases: primeiro a fundação de banco, models e frontend; depois os fluxos administrativos e de cliente na ordem de dependência — cadastro e isolamento, OAuth e sincronização do Pipedrive, consulta dos dados importados, link mágico, seleção de tenant, conversa com IA e consulta administrativa das conversas. Cada fase é autocontida para uma sessão do Ralph e referencia explicitamente histórias, tabelas e workflows.

O corte do MVP ocorre ao final da **Phase 11**, quando os seis workflows estão implementados e cobertos por testes de isolamento multi-tenant. Na ausência de `.spec/init/design/`, as telas usarão provisoriamente os layouts, componentes Radix e padrões visuais já presentes no starter kit. A abstração de IA será implementada, mas o provedor e o modelo iniciais continuam como questão aberta antes da execução da Phase 10.

**Conventions:**
- `[ ]` pending · `[x]` done in the codebase.
- Phases and sub-phases are numbered (`Phase 1`, `Phase 5.3`) for reference by AI agents.
- Business-logic tasks list the **feature tests** to generate; frontend-only tasks list validatable **acceptance criteria** and a **Design ref**.
- Todos os comandos PHP, Artisan, Composer e Node devem ser executados por Laravel Sail.
- Cada fase deve terminar com os testes afetados, Pint para PHP alterado, análise estática aplicável e verificações de frontend quando houver mudanças em React/TypeScript.

---

## Phase 1: Foundation — Database

**Goal:** Materializar integralmente o schema aprovado em migrations PostgreSQL e seeders idempotentes. · **Depends on:** none · **Covers:** todas as tabelas de `.spec/init/database-schema.md`, fundação dos Workflows 1–6

### Phase 1.1: Tenancy, lookups e integração CRM

- [ ] **Task:** Criar as migrations das lookup tables e da raiz multi-tenant.
  - **Acceptance criteria:**
    - `crm_connection_statuses`, `crm_sync_statuses`, `conversation_statuses`, `message_roles` e `companies` reproduzem tipos, defaults, unicidades e timestamps do DBML.
    - O rollback remove as tabelas em ordem segura e nenhuma tabela usa enum de banco ou soft delete.
  - **Feature tests:** `DatabaseSchemaTest` → confirma colunas, índices únicos e defaults das lookup tables e de `companies`.
  - **Traces:** `crm_connection_statuses`, `crm_sync_statuses`, `conversation_statuses`, `message_roles`, `companies`, US-1.2

- [ ] **Task:** Adaptar a migration de usuários para tornar a empresa obrigatória e única por administrador no MVP.
  - **Acceptance criteria:**
    - `users.company_id` referencia `companies.id`, é obrigatório e único.
    - A migration preserva as colunas de autenticação, passkeys, reset de senha e sessão administrativa já instaladas.
  - **Feature tests:** `CompanyUserSchemaTest` → impede dois administradores na mesma empresa e usuário sem empresa.
  - **Traces:** `users`, `companies`, US-1.1, US-1.2

- [ ] **Task:** Criar as migrations de conexão e execução de sincronização do CRM.
  - **Acceptance criteria:**
    - `crm_connections` impõe uma conexão por empresa, referencia seu status e armazena tokens, autorização, expiração e último erro conforme o schema.
    - `crm_sync_runs` referencia empresa, conexão e status, com os índices compostos especificados.
    - Foreign keys não usam cascata capaz de atravessar ou apagar dados de outro tenant.
  - **Feature tests:** `CrmConnectionSchemaTest` → valida unicidade por tenant, FKs e índices de `crm_connections` e `crm_sync_runs`.
  - **Traces:** `crm_connections`, `crm_sync_runs`, US-2.1, US-2.2, US-2.3

### Phase 1.2: Dados locais do Pipedrive

- [ ] **Task:** Criar as migrations da estrutura e dos usuários sincronizados do Pipedrive.
  - **Acceptance criteria:**
    - `pipedrive_pipelines`, `pipedrive_stages`, `pipedrive_custom_fields` e `pipedrive_users` possuem tenant, conexão, identificador externo, payload bruto e instante de sincronização.
    - Unicidades e ordenações compostas seguem o DBML, inclusive a chave de campo personalizado por empresa, entidade e chave.
  - **Feature tests:** `PipedriveStructureSchemaTest` → valida FKs tenant-local, índices e unicidades das quatro tabelas.
  - **Traces:** `pipedrive_pipelines`, `pipedrive_stages`, `pipedrive_custom_fields`, `pipedrive_users`, US-2.2, US-2.3

- [ ] **Task:** Criar as migrations de organizações, pessoas e e-mails sincronizados.
  - **Acceptance criteria:**
    - `pipedrive_organizations`, `pipedrive_people` e `pipedrive_person_emails` preservam relações opcionais, flags, payloads e índices do schema.
    - O mesmo e-mail pode existir em tenants diferentes, mas não é duplicado funcionalmente para a mesma pessoa.
  - **Feature tests:** `PipedriveContactsSchemaTest` → valida relações, nulabilidade e unicidade de e-mail por pessoa.
  - **Traces:** `pipedrive_organizations`, `pipedrive_people`, `pipedrive_person_emails`, US-2.2, US-3.1

- [ ] **Task:** Criar as migrations de negócios, produtos, itens e atividades.
  - **Acceptance criteria:**
    - `pipedrive_deals`, `pipedrive_products`, `pipedrive_deal_products` e `pipedrive_activities` reproduzem precisões decimais, relações opcionais e índices do DBML.
    - A pivot possui chave primária composta e não permite repetir o mesmo produto no mesmo negócio.
  - **Feature tests:** `PipedriveSalesSchemaTest` → valida chaves, precisões, índices e pivot composta.
  - **Traces:** `pipedrive_deals`, `pipedrive_products`, `pipedrive_deal_products`, `pipedrive_activities`, US-2.2, US-2.3

### Phase 1.3: Cliente e conversas

- [ ] **Task:** Criar as migrations de identidade, vínculo, link mágico e sessão do cliente.
  - **Acceptance criteria:**
    - `customer_identities`, `company_customers`, `customer_magic_links` e `customer_sessions` seguem exatamente chaves, nulabilidade, unicidades e índices do DBML.
    - Tokens são persistidos somente como hash e a sessão pode ter empresa ativa nula até a seleção válida.
  - **Feature tests:** `CustomerAccessSchemaTest` → valida e-mail global único, vínculos tenant-local, token único e índices de expiração.
  - **Traces:** `customer_identities`, `company_customers`, `customer_magic_links`, `customer_sessions`, US-3.1, US-3.2, US-4.1, US-4.2

- [ ] **Task:** Adaptar as migrations do Laravel AI ao domínio multi-tenant sem quebrar o contrato do pacote.
  - **Acceptance criteria:**
    - `agent_conversations` recebe `company_id`, `company_customer_id`, `status_id`, unicidade por empresa/cliente e as FKs do schema.
    - `agent_conversation_messages` recebe `company_id`, `message_role_id`, FK de conversa e índices multi-tenant, preservando todas as colunas exigidas pelo Laravel AI.
    - A ordem das migrations permite `migrate:fresh` e rollback completos no PostgreSQL.
  - **Feature tests:** `AgentConversationSchemaTest` → valida UUIDs, compatibilidade de colunas, unicidade da conversa e índices tenant-local.
  - **Traces:** `agent_conversations`, `agent_conversation_messages`, US-4.3, US-5.1, US-5.2, US-6.1

- [ ] **Task:** Implementar seeders idempotentes para todos os valores categóricos iniciais.
  - **Acceptance criteria:**
    - Os seeders criam exatamente os slugs documentados para status de conexão, sincronização, conversa e papéis de mensagem.
    - Reexecutar o seeder não duplica registros nem altera slugs canônicos.
  - **Feature tests:** `LookupSeederTest` → executa os seeders duas vezes e confirma conjuntos e contagens estáveis.
  - **Traces:** `crm_connection_statuses`, `crm_sync_statuses`, `conversation_statuses`, `message_roles`

- [ ] **Task:** Validar a fundação completa em banco limpo.
  - **Acceptance criteria:**
    - `migrate:fresh --seed` conclui dentro do Sail usando PostgreSQL.
    - A suite de schema passa e detecta as restrições críticas de tenancy e idempotência.
  - **Feature tests:** `DatabaseFoundationTest` → cobre fresh migration, seed inicial e restrições críticas do schema.
  - **Traces:** todas as tabelas, Workflows 1–6

---

## Phase 2: Foundation — Models and Relationships

**Goal:** Criar models, factories e relações Eloquent completas para todas as entidades do domínio. · **Depends on:** Phase 1 · **Covers:** todas as relações do database schema, US-1.2, US-2.2, US-4.3, US-5.1

### Phase 2.1: Tenancy e CRM

- [ ] **Task:** Implementar `Company`, adaptar `User` e criar factories tenant-aware.
  - **Acceptance criteria:**
    - `Company` e `User` expõem relações inversas tipadas; `User` inclui `company_id` em fillable/documentação e mantém casts de autenticação.
    - Factories criam por padrão um conjunto válido sem compartilhar empresa acidentalmente.
  - **Feature tests:** `CompanyRelationshipsTest` → comprova administrador único e navegação bidirecional empresa/usuário.
  - **Traces:** `companies`, `users`, US-1.1, US-1.2

- [ ] **Task:** Implementar models e factories de lookups, conexão CRM e execuções de sincronização.
  - **Acceptance criteria:**
    - Models de lookup, `CrmConnection` e `CrmSyncRun` possuem relações, casts de boolean/datetime e atributos sensíveis protegidos.
    - Tokens OAuth usam cast criptografado recuperável e não aparecem em serialização pública.
  - **Feature tests:** `CrmModelsTest` → valida relações, casts e confidencialidade dos tokens.
  - **Traces:** `crm_connection_statuses`, `crm_sync_statuses`, `crm_connections`, `crm_sync_runs`, US-2.1, US-2.2

### Phase 2.2: Models do Pipedrive

- [ ] **Task:** Implementar models de pipeline, etapa, campo personalizado e usuário Pipedrive.
  - **Acceptance criteria:**
    - Todas as relações com empresa e conexão estão completas; pipeline/etapa possuem relação bidirecional.
    - Payloads e opções usam casts de array, flags usam boolean e datas usam datetime.
  - **Feature tests:** `PipedriveStructureModelsTest` → valida relações e casts com factories de tenants distintos.
  - **Traces:** `pipedrive_pipelines`, `pipedrive_stages`, `pipedrive_custom_fields`, `pipedrive_users`, US-2.2

- [ ] **Task:** Implementar models de organização, pessoa e e-mail Pipedrive.
  - **Acceptance criteria:**
    - Relações opcionais com organização e responsável e relações pessoa/e-mails estão completas.
    - Factories oferecem estados coerentes com e sem relações opcionais.
  - **Feature tests:** `PipedriveContactModelsTest` → valida toda a árvore organização/pessoa/e-mail e seu tenant.
  - **Traces:** `pipedrive_organizations`, `pipedrive_people`, `pipedrive_person_emails`, US-2.2, US-3.1

- [ ] **Task:** Implementar models de negócio, produto, pivot e atividade Pipedrive.
  - **Acceptance criteria:**
    - `PipedriveDeal` e `PipedriveProduct` expõem relação many-to-many com atributos da pivot.
    - Relações opcionais de negócio/atividade e casts decimais, booleanos, JSON e datetime estão completos.
  - **Feature tests:** `PipedriveSalesModelsTest` → valida relações, pivot e casts sem atravessar tenant.
  - **Traces:** `pipedrive_deals`, `pipedrive_products`, `pipedrive_deal_products`, `pipedrive_activities`, US-2.2

### Phase 2.3: Models de cliente e conversa

- [ ] **Task:** Implementar models de identidade, vínculo, link mágico e sessão do cliente.
  - **Acceptance criteria:**
    - As relações entre identidade, empresas, pessoas/e-mails, links e sessões são completas e tipadas.
    - Tokens não são serializados; timestamps de expiração, uso, atividade e revogação possuem casts datetime.
  - **Feature tests:** `CustomerAccessModelsTest` → valida relações multiempresa, casts e ocultação de hashes.
  - **Traces:** `customer_identities`, `company_customers`, `customer_magic_links`, `customer_sessions`, US-3.1, US-3.2, US-4.1

- [ ] **Task:** Implementar models de status, papel, conversa e mensagem compatíveis com Laravel AI.
  - **Acceptance criteria:**
    - Conversa e mensagem preservam o comportamento esperado pelo pacote e adicionam relações com empresa, cliente, status e papel.
    - O papel canônico e o campo `role` compatível podem ser validados como equivalentes pela camada de domínio.
  - **Feature tests:** `AgentConversationModelsTest` → valida relações, casts, UUIDs e consistência de papel.
  - **Traces:** `conversation_statuses`, `message_roles`, `agent_conversations`, `agent_conversation_messages`, US-5.1, US-5.2

- [ ] **Task:** Introduzir escopos e invariantes reutilizáveis de tenancy nos models pertencentes à empresa.
  - **Acceptance criteria:**
    - Há uma abordagem única e explícita para filtrar por empresa e validar referências redundantes do mesmo tenant.
    - Queries administrativas e de cliente podem aplicar o escopo sem depender de IDs fornecidos pelo navegador.
  - **Feature tests:** `TenantModelScopeTest` → tenta combinar entidades de duas empresas e comprova recusa/filtragem.
  - **Traces:** US-1.2, US-2.2, US-4.3, `companies`, `company_customers`, `agent_conversations`, `agent_conversation_messages`

---

## Phase 3: Foundation — Frontend and Application Shell

**Goal:** Consolidar a base visual e de navegação para as áreas administrativa e do cliente sem inventar um design definitivo. · **Depends on:** Phase 2 · **Covers:** fundação visual dos Workflows 1–6

### Phase 3.1: Base administrativa existente

- [x] **Task:** Adotar os layouts responsivos, navegação, feedback e componentes Radix existentes como design provisório do MVP.
  - **Acceptance criteria:**
    - `AppLayout`, layouts de autenticação, sidebar/header, botões, inputs, cards, dialogs, alerts, badges, skeletons, spinner e toast já estão disponíveis e compilam.
    - Componentes preservam estados claro/escuro, foco e comportamento responsivo do starter kit.
  - **Design ref:** provisório — implementação existente em `resources/js/layouts` e `resources/js/components`; `.spec/init/design/` ausente.
  - **Traces:** `resources/js/layouts`, `resources/js/components`, Workflows 1, 2 e 6

- [ ] **Task:** Organizar a navegação administrativa para integrações, dados sincronizados e conversas.
  - **Acceptance criteria:**
    - A sidebar apresenta somente destinos do MVP permitidos ao administrador autenticado.
    - Itens ativos, breadcrumbs e estados vazios seguem os componentes já existentes.
  - **Design ref:** provisório — starter kit existente; `.spec/init/design/` ausente por decisão deste planejamento.
  - **Traces:** US-1.2, US-2.4, US-6.1

### Phase 3.2: Base pública e do cliente

- [ ] **Task:** Criar um shell público/cliente separado da área administrativa.
  - **Acceptance criteria:**
    - Solicitação de link, seleção de empresa e chat compartilham um layout responsivo sem sidebar administrativa.
    - O shell suporta estados de carregamento, erro, vazio e feedback sem expor dados administrativos.
  - **Design ref:** provisório — auth layouts e componentes existentes; `.spec/init/design/` ausente.
  - **Traces:** US-3.1, US-3.3, US-4.2, US-5.2

- [ ] **Task:** Definir tipos TypeScript compartilhados e padrões de props para empresa, status, paginação e mensagens.
  - **Acceptance criteria:**
    - Props de páginas não duplicam estruturas incompatíveis e não incluem tokens, hashes ou payloads sensíveis.
    - TypeScript compila sem `any` introduzido para entidades do domínio.
  - **Traces:** Workflows 1–6, `companies`, `crm_sync_runs`, `agent_conversations`, `agent_conversation_messages`

---

## Phase 4: Company Registration and Tenant Isolation

**Goal:** Transformar o cadastro Fortify existente em criação atômica de empresa e administrador e estabelecer autorização tenant-aware. · **Depends on:** Phase 3 · **Covers:** US-1.1, US-1.2, Workflow 1

### Phase 4.1: Cadastro transacional

- [ ] **Task:** Estender o formulário de cadastro com os dados obrigatórios da empresa.
  - **Acceptance criteria:**
    - A tela solicita nome da empresa, nome do administrador, e-mail e senha com erros por campo.
    - O fluxo não oferece papéis, permissões ou administradores adicionais.
  - **Design ref:** provisório — `resources/js/pages/auth/register.tsx` e auth layout existentes.
  - **Traces:** US-1.1, `companies`, `users`

- [ ] **Task:** Adaptar `CreateNewUser` para criar empresa e administrador em uma única transação.
  - **Acceptance criteria:**
    - Sucesso cria exatamente uma empresa e um usuário associado e autentica o administrador.
    - Qualquer falha reverte ambos os registros e preserva validação de e-mail/senha do Fortify.
  - **Feature tests:** `CompanyRegistrationTest` → cobre sucesso, validação, unicidade e rollback atômico.
  - **Traces:** US-1.1, `companies`, `users`

### Phase 4.2: Contexto e autorização do tenant administrativo

- [ ] **Task:** Disponibilizar o tenant do administrador autenticado de forma centralizada em requests e props Inertia.
  - **Acceptance criteria:**
    - Controllers e serviços obtêm a empresa pelo usuário autenticado, nunca por um `company_id` arbitrário do cliente.
    - Props globais expõem somente identificação não sensível da empresa atual.
  - **Feature tests:** `AuthenticatedTenantContextTest` → valida resolução do tenant e ausência de contexto para visitante.
  - **Traces:** US-1.2, `companies`, `users`

- [ ] **Task:** Implementar policies ou autorização equivalente para recursos administrativos tenant-owned.
  - **Acceptance criteria:**
    - Conexão, sync runs, dados sincronizados, clientes e conversas de outra empresa resultam em resposta não reveladora.
    - Route model binding não permite atravessar tenant por alteração de identificador.
  - **Feature tests:** `AdministrativeTenantIsolationTest` → testa leitura e mutação com recursos de duas empresas.
  - **Traces:** US-1.2, `crm_connections`, `crm_sync_runs`, `company_customers`, `agent_conversations`

- [ ] **Task:** Substituir o dashboard placeholder por um resumo tenant-aware do estado inicial.
  - **Acceptance criteria:**
    - O dashboard identifica a empresa e oferece acesso à conexão CRM, dados sincronizados e conversas.
    - Contagens ou estados apresentados são sempre filtrados pelo tenant autenticado.
  - **Feature tests:** `DashboardTenantTest` → comprova que dados de outra empresa não alteram o dashboard.
  - **Design ref:** provisório — `resources/js/pages/dashboard.tsx` e AppLayout existentes.
  - **Traces:** US-1.2, Workflow 1

---

## Phase 5: Pipedrive OAuth Connection

**Goal:** Autorizar uma conta Pipedrive de leitura e vinculá-la com segurança ao tenant administrativo. · **Depends on:** Phase 4 · **Covers:** US-2.1, Workflow 2

### Phase 5.1: Cliente e configuração Pipedrive

- [ ] **Task:** Centralizar configuração OAuth e implementar um contrato de cliente Pipedrive testável.
  - **Acceptance criteria:**
    - Client ID, secret, redirect URI, scopes e endpoints vêm de configuração/ambiente e não são persistidos em código.
    - A camada de aplicação depende de um contrato substituível por fake nos testes.
  - **Feature tests:** `PipedriveConfigurationTest` → valida falha clara sem configuração e substituição por fake.
  - **Traces:** US-2.1, `crm_connections`

- [ ] **Task:** Implementar início OAuth com state assinado e tenant autenticado.
  - **Acceptance criteria:**
    - Somente administrador autenticado inicia o fluxo; o state associa a tentativa à sessão e empresa sem expor segredo.
    - A autorização solicita apenas os scopes de leitura necessários ao MVP.
  - **Feature tests:** `PipedriveOAuthStartTest` → valida autenticação, state, redirect e scopes.
  - **Traces:** US-2.1, `companies`, `crm_connections`

- [ ] **Task:** Implementar callback OAuth e persistência segura da conexão.
  - **Acceptance criteria:**
    - Callback válido troca o código, cria ou atualiza somente a conexão da empresa que iniciou o fluxo e marca status/authorization timestamp.
    - Tokens são cifrados em repouso; callback negado, inválido, expirado ou com state divergente não produz conexão ativa.
  - **Feature tests:** `PipedriveOAuthCallbackTest` → cobre sucesso, reconexão, negação, state inválido e isolamento entre tenants.
  - **Traces:** US-2.1, `crm_connection_statuses`, `crm_connections`

### Phase 5.2: Experiência administrativa da conexão

- [ ] **Task:** Criar tela de integração Pipedrive com estado e ações permitidas.
  - **Acceptance criteria:**
    - A página mostra desconectado, pendente, ativo, falho, revogado ou expirado sem revelar tokens.
    - Conectar/reconectar usa rotas tipadas e feedback claro; conexão de outro tenant nunca aparece.
  - **Feature tests:** `PipedriveConnectionPageTest` → valida props por status e isolamento tenant.
  - **Design ref:** provisório — AppLayout, cards, badges, alerts e buttons existentes.
  - **Traces:** US-2.1, US-1.2, `crm_connections`

---

## Phase 6: Pipedrive Initial Synchronization

**Goal:** Importar e reprocessar de forma idempotente todos os dados Pipedrive previstos, sempre dentro do tenant da conexão. · **Depends on:** Phase 5 · **Covers:** US-2.2, US-2.3, Workflow 2

### Phase 6.1: Orquestração e estados

- [ ] **Task:** Implementar o serviço de sincronização e seu ciclo de estados.
  - **Acceptance criteria:**
    - Cada execução cria `crm_sync_runs` em `pending/running` e termina em `succeeded/failed` com timestamps e erro sanitizado.
    - Só conexão ativa do tenant autenticado pode iniciar; falha permite nova tentativa manual.
  - **Feature tests:** `CrmSynchronizationLifecycleTest` → cobre sucesso, falha, retry e conexão inválida/de outro tenant.
  - **Traces:** US-2.2, US-2.3, `crm_sync_runs`, `crm_sync_statuses`, `crm_connections`

- [ ] **Task:** Definir paginação, retry de transporte e fronteiras transacionais sem sincronização contínua.
  - **Acceptance criteria:**
    - Coleções paginadas são consumidas até o fim com retry limitado para falhas transitórias.
    - Não são criados webhook, scheduler ou recorrência; falhas não associam registros ao tenant errado.
  - **Feature tests:** `PipedriveSyncTransportTest` → simula múltiplas páginas, falha transitória, falha definitiva e ausência de agendamento.
  - **Traces:** US-2.2, US-2.3, Workflow 2

### Phase 6.2: Importadores idempotentes

- [ ] **Task:** Sincronizar pipelines, etapas, campos personalizados e usuários por upsert tenant-aware.
  - **Acceptance criteria:**
    - Upsert usa as chaves únicas do schema e atualiza payload, flags e `synced_at` sem duplicar.
    - Relações de etapa/pipeline e referências a usuários permanecem no mesmo tenant/conexão.
  - **Feature tests:** `PipedriveStructureSyncTest` → cobre criação, atualização, repetição idempotente e colisão de IDs entre tenants.
  - **Traces:** US-2.2, US-2.3, `pipedrive_pipelines`, `pipedrive_stages`, `pipedrive_custom_fields`, `pipedrive_users`

- [ ] **Task:** Sincronizar organizações, pessoas e todos os e-mails normalizados.
  - **Acceptance criteria:**
    - Pessoas preservam organização/responsável quando disponíveis e todos os e-mails elegíveis são normalizados em registros próprios.
    - Reprocessamento atualiza a coleção de e-mails sem duplicatas funcionais e sem reutilizar relações de outro tenant.
  - **Feature tests:** `PipedriveContactsSyncTest` → cobre relações opcionais, múltiplos e-mails, atualização e isolamento.
  - **Traces:** US-2.2, US-2.3, `pipedrive_organizations`, `pipedrive_people`, `pipedrive_person_emails`

- [ ] **Task:** Sincronizar negócios, produtos, itens de negócio e atividades.
  - **Acceptance criteria:**
    - Negócios e atividades ligam somente entidades previamente importadas na mesma empresa, tolerando referências opcionais ausentes.
    - Itens de negócio sincronizam quantidade, preço e desconto e reprocessamento mantém a pivot idempotente.
  - **Feature tests:** `PipedriveSalesSyncTest` → cobre relações completas/parciais, pivot, atualização e isolamento.
  - **Traces:** US-2.2, US-2.3, `pipedrive_deals`, `pipedrive_products`, `pipedrive_deal_products`, `pipedrive_activities`

- [ ] **Task:** Materializar identidades e vínculos de clientes a partir dos e-mails sincronizados.
  - **Acceptance criteria:**
    - Cada e-mail normalizado encontra/cria uma identidade global e um vínculo `company_customers` com pessoa e e-mail do mesmo tenant.
    - O mesmo e-mail em empresas diferentes compartilha apenas a identidade, nunca pessoa, payload ou conversa; reexecução é idempotente.
  - **Feature tests:** `CompanyCustomerProjectionTest` → cobre uma/múltiplas empresas, reexecução e rejeição de referências cruzadas.
  - **Traces:** US-2.2, US-2.3, US-3.1, `customer_identities`, `company_customers`, `pipedrive_person_emails`

### Phase 6.3: Disparo e acompanhamento manual

- [ ] **Task:** Expor ação administrativa de sincronização manual com proteção contra execução concorrente da mesma conexão.
  - **Acceptance criteria:**
    - Administrador inicia ou repete somente a própria sincronização e recebe o identificador/estado da execução.
    - Uma execução `running` impede outra concorrente para a mesma conexão sem bloquear empresas distintas.
  - **Feature tests:** `StartPipedriveSyncTest` → cobre autorização, concorrência por conexão e paralelismo entre tenants.
  - **Traces:** US-2.2, US-2.3, `crm_sync_runs`

- [ ] **Task:** Exibir na integração o histórico e o resultado da sincronização.
  - **Acceptance criteria:**
    - A página mostra estados, início/fim e erro sanitizado das execuções do tenant, com ação de nova tentativa após falha.
    - Nenhuma execução ou mensagem de erro de outra empresa é retornada.
  - **Feature tests:** `CrmSyncHistoryPageTest` → valida ordenação, estados, retry e isolamento.
  - **Design ref:** provisório — AppLayout, cards, table/list, badges e alerts existentes.
  - **Traces:** US-2.2, US-2.3, `crm_sync_runs`

---

## Phase 7: Administrative CRM Data Browser

**Goal:** Permitir inspeção somente leitura da representação local do Pipedrive pelo administrador do tenant. · **Depends on:** Phase 6 · **Covers:** US-2.4, US-1.2, Workflow 2

### Phase 7.1: Consultas tenant-aware

- [ ] **Task:** Implementar consultas paginadas e filtráveis para cada categoria sincronizada.
  - **Acceptance criteria:**
    - Pipelines/etapas, campos, usuários, organizações, pessoas/e-mails, negócios/produtos e atividades são consultados somente no tenant atual.
    - Tipos ausentes retornam estado vazio e paginação/filtros não permitem injetar outro `company_id`.
  - **Feature tests:** `SyncedDataIndexTest` → cobre cada categoria, paginação, filtro, vazio e tenants com IDs externos iguais.
  - **Traces:** US-2.4, US-1.2, `pipedrive_pipelines`, `pipedrive_stages`, `pipedrive_custom_fields`, `pipedrive_users`, `pipedrive_organizations`, `pipedrive_people`, `pipedrive_person_emails`, `pipedrive_deals`, `pipedrive_products`, `pipedrive_activities`

- [ ] **Task:** Implementar detalhes somente leitura preservando relações e ocultando dados internos sensíveis.
  - **Acceptance criteria:**
    - Detalhes apresentam relações úteis do registro no mesmo tenant e nunca oferecem mutação no Pipedrive.
    - Tokens, hashes e payload bruto integral não são expostos por padrão ao frontend.
  - **Feature tests:** `SyncedDataShowTest` → cobre autorização por categoria, relações tenant-local e ausência de segredos.
  - **Traces:** US-2.4, `crm_connections`, dados sincronizados

### Phase 7.2: Telas administrativas

- [ ] **Task:** Criar a página de navegação dos dados sincronizados com categorias e estados vazios.
  - **Acceptance criteria:**
    - O administrador identifica o tipo importado, navega por listas paginadas e entende quando uma categoria está vazia.
    - A interface é somente leitura e responsiva.
  - **Feature tests:** `SyncedDataPageTest` → valida props permitidas e ausência de recursos de outro tenant.
  - **Design ref:** provisório — AppLayout, navigation, cards, badges e skeletons existentes.
  - **Traces:** US-2.4

- [ ] **Task:** Criar páginas de lista/detalhe reutilizáveis para os tipos sincronizados.
  - **Acceptance criteria:**
    - Campos comuns e relações são renderizados de modo consistente sem misturar entidades ou tenants.
    - Loading, vazio e erro usam feedback visual existente; não há botões de escrita no CRM.
  - **Design ref:** provisório — componentes existentes; `.spec/init/design/` ausente.
  - **Traces:** US-2.4, Workflow 2

---

## Phase 8: Customer Magic-Link Authentication

**Goal:** Autenticar somente clientes elegíveis por link único e sessão temporária configurável. · **Depends on:** Phase 7 · **Covers:** US-3.1, US-3.2, US-3.3, Workflow 3

### Phase 8.1: Emissão segura

- [ ] **Task:** Centralizar duração do link e da sessão do cliente em configuração validável.
  - **Acceptance criteria:**
    - Defaults são 15 minutos para link e 24 horas para sessão e podem ser alterados por ambiente.
    - Valores inválidos falham de forma explícita e não ficam duplicados no fluxo.
  - **Feature tests:** `CustomerAuthConfigurationTest` → valida defaults e overrides.
  - **Traces:** US-3.1, US-3.2, `customer_magic_links`, `customer_sessions`

- [ ] **Task:** Implementar solicitação de link para e-mail associado a pelo menos uma empresa.
  - **Acceptance criteria:**
    - E-mail é normalizado e consultado pela identidade/vínculos sincronizados; elegível gera token aleatório, persiste somente hash e envia URL de uso único.
    - E-mail não associado não gera registro nem envio e recebe a resposta especificada sem revelar dados de empresas.
  - **Feature tests:** `RequestCustomerMagicLinkTest` → cobre elegível em uma/múltiplas empresas, inelegível, normalização, hash e envio.
  - **Traces:** US-3.1, `customer_identities`, `company_customers`, `customer_magic_links`

- [ ] **Task:** Implementar notificação de link mágico sem acoplar o domínio ao transporte de e-mail.
  - **Acceptance criteria:**
    - Mensagem contém URL válida e expiração, sem token em logs ou banco em texto puro.
    - Testes usam fake e não dependem de serviço externo.
  - **Feature tests:** `CustomerMagicLinkNotificationTest` → valida destinatário, URL, expiração e ausência de segredo persistido.
  - **Traces:** US-3.1, `customer_magic_links`

### Phase 8.2: Consumo e sessão

- [ ] **Task:** Consumir o link atomicamente e criar sessão de cliente.
  - **Acceptance criteria:**
    - Link existente, não expirado e não usado é marcado como usado na mesma transação que cria a sessão com hash, expiração e metadados.
    - Uso simultâneo ou repetido não cria uma segunda sessão.
  - **Feature tests:** `ConsumeCustomerMagicLinkTest` → cobre sucesso, single-use, concorrência lógica e duração configurada.
  - **Traces:** US-3.2, `customer_magic_links`, `customer_sessions`

- [ ] **Task:** Recusar links inexistentes, adulterados, expirados ou utilizados sem autenticar.
  - **Acceptance criteria:**
    - Nenhum caso inválido cria sessão ou define tenant ativo.
    - A resposta é clara, mas não revela identidade, empresas ou histórico.
  - **Feature tests:** `InvalidCustomerMagicLinkTest` → cobre todas as classes de credencial inválida e ausência de efeitos colaterais.
  - **Traces:** US-3.3, `customer_magic_links`, `customer_sessions`

- [ ] **Task:** Implementar guard/middleware da sessão de cliente separado da autenticação administrativa.
  - **Acceptance criteria:**
    - Sessão válida resolve a identidade; expirada ou revogada é recusada e pode ser encerrada sem afetar sessão administrativa.
    - Token de sessão fica em cookie seguro adequado e nunca concede acesso às rotas administrativas.
  - **Feature tests:** `CustomerSessionMiddlewareTest` → cobre validade, expiração, revogação, separação de guards e segurança do cookie.
  - **Traces:** US-3.2, US-3.3, US-4.3, `customer_sessions`

### Phase 8.3: Telas de acesso

- [ ] **Task:** Criar telas públicas de solicitação, confirmação e erro do link mágico.
  - **Acceptance criteria:**
    - Cliente informa e-mail, recebe feedback de envio/não associação e vê estados claros para link inválido ou expirado.
    - Formulários possuem loading, validação e acessibilidade básica sem expor tenants associados.
  - **Feature tests:** `CustomerMagicLinkPagesTest` → valida respostas Inertia e props sem dados sensíveis.
  - **Design ref:** provisório — shell público e auth components da Phase 3.
  - **Traces:** US-3.1, US-3.2, US-3.3

---

## Phase 9: Customer Tenant Selection and Authorization

**Goal:** Definir somente um tenant autorizado como contexto ativo da sessão do cliente. · **Depends on:** Phase 8 · **Covers:** US-4.1, US-4.2, US-4.3, Workflow 4

### Phase 9.1: Resolução e seleção

- [ ] **Task:** Resolver empresas autorizadas a partir dos vínculos atuais da identidade autenticada.
  - **Acceptance criteria:**
    - A lista deriva de `company_customers`, contém somente empresas vinculadas e reflete alterações atuais dos vínculos.
    - Nenhum dado CRM ou administrativo acompanha a listagem.
  - **Feature tests:** `CustomerAvailableCompaniesTest` → cobre zero, uma, múltiplas associações e ausência de dados sensíveis.
  - **Traces:** US-4.1, US-4.2, `customer_identities`, `company_customers`, `companies`

- [ ] **Task:** Selecionar automaticamente a única empresa autorizada.
  - **Acceptance criteria:**
    - Com exatamente uma associação, `customer_sessions.active_company_id` é definido sem tela de escolha.
    - A definição não concede acesso administrativo nem a dados sincronizados.
  - **Feature tests:** `AutomaticCompanySelectionTest` → valida única associação, sessão atualizada e limites de acesso.
  - **Traces:** US-4.1, `customer_sessions`, `company_customers`

- [ ] **Task:** Implementar seleção explícita entre múltiplas empresas autorizadas.
  - **Acceptance criteria:**
    - Antes da escolha nenhuma conversa pode ser aberta; submissão aceita apenas empresa presente nos vínculos da identidade.
    - ID manipulado ou empresa desvinculada é recusado sem alterar o tenant ativo.
  - **Feature tests:** `SelectCustomerCompanyTest` → cobre seleção válida, ID não associado, vínculo removido e troca controlada.
  - **Traces:** US-4.2, US-4.3, `customer_sessions`, `company_customers`

### Phase 9.2: Enforcement e interface

- [ ] **Task:** Implementar middleware de tenant ativo para todas as rotas de conversa do cliente.
  - **Acceptance criteria:**
    - Cada request valida sessão, identidade, empresa ativa e vínculo atual antes de acessar conversa ou mensagem.
    - Identificadores manipulados não revelam existência nem conteúdo de outro tenant.
  - **Feature tests:** `ActiveCustomerTenantMiddlewareTest` → cobre ausência de seleção, sessão expirada, vínculo removido e tentativa cross-tenant.
  - **Traces:** US-4.3, `customer_sessions`, `company_customers`, `agent_conversations`, `agent_conversation_messages`

- [ ] **Task:** Criar tela de seleção de empresa para clientes com múltiplos vínculos.
  - **Acceptance criteria:**
    - A página lista somente nome/identificação das empresas permitidas e bloqueia continuação sem escolha.
    - Manipulação da requisição é tratada no backend; loading e erro são apresentados no shell público.
  - **Feature tests:** `CompanySelectionPageTest` → valida props, redirecionamento com única empresa e isolamento.
  - **Design ref:** provisório — shell público, cards, buttons e alerts da Phase 3.
  - **Traces:** US-4.2, US-4.3

---

## Phase 10: Persistent Conversation with the Global Agent

**Goal:** Criar ou retomar uma conversa tenant-local e persistir a troca com um agente global desacoplado do provedor. · **Depends on:** Phase 9 · **Covers:** US-5.1, US-5.2, US-5.3, Workflow 5

### Phase 10.1: Contrato e configuração de IA

- [ ] **Task:** Definir o contrato de geração de resposta e a configuração centralizada de provedor/modelo.
  - **Acceptance criteria:**
    - Regras de conversa dependem de uma interface/serviço da aplicação substituível em testes.
    - Provedor, modelo e System Prompt global vêm de configuração; o prompt é versionado no código e não aceita override por empresa.
    - A execução desta tarefa exige resolver a questão aberta de provedor/modelo antes de integrar o adapter real.
  - **Feature tests:** `AiProviderAbstractionTest` → troca fake/adapter sem alterar autenticação, tenancy ou persistência.
  - **Traces:** US-5.2, US-5.3, Agente global, Contexto do agente

- [ ] **Task:** Implementar o agente Laravel AI global sem tools, actions, RAG ou contexto Pipedrive.
  - **Acceptance criteria:**
    - O adapter envia somente System Prompt global, histórico autorizado e mensagem atual.
    - Nenhum model/tabela Pipedrive é consultado para compor o contexto e o agente não registra tools.
  - **Feature tests:** `GlobalSalesAgentContextTest` → inspeciona request fake e comprova ausência de dados tenant/CRM, tools e customização de prompt.
  - **Traces:** US-5.2, US-5.3, `agent_conversation_messages`

### Phase 10.2: Conversa persistente

- [ ] **Task:** Criar ou retomar uma única conversa por empresa e cliente autorizado.
  - **Acceptance criteria:**
    - Primeira abertura cria conversa com UUID, empresa, `company_customer`, status ativo e título; acessos posteriores retornam a mesma conversa.
    - Nova autenticação após expiração da sessão não apaga histórico; outro tenant produz conversa distinta.
  - **Feature tests:** `OpenCustomerConversationTest` → cobre criação, retomada, unicidade, nova sessão e separação por empresa.
  - **Traces:** US-5.1, `agent_conversations`, `company_customers`, `conversation_statuses`

- [ ] **Task:** Persistir mensagem do cliente antes da geração, com tenant e papel consistentes.
  - **Acceptance criteria:**
    - Mensagem `user` pertence à conversa/empresa autorizada e mantém `role` compatível com `message_role_id`.
    - Conteúdo inválido é recusado e IDs de conversa/cliente enviados pelo navegador não atravessam o tenant ativo.
  - **Feature tests:** `SendCustomerMessageTest` → cobre persistência, validação de conteúdo, consistência de papel e tentativa cross-tenant.
  - **Traces:** US-5.2, US-4.3, `agent_conversation_messages`, `message_roles`

- [ ] **Task:** Gerar e persistir a resposta do agente preservando ordem e atomicidade adequada.
  - **Acceptance criteria:**
    - Resposta bem-sucedida é persistida como `assistant` na mesma conversa/empresa e retorna após a mensagem do cliente.
    - Histórico fornecido e exibido segue ordem determinística por criação/ID.
  - **Feature tests:** `GenerateAgentReplyTest` → cobre request ao provider fake, persistência da resposta, papel e ordenação.
  - **Traces:** US-5.2, `agent_conversations`, `agent_conversation_messages`, `message_roles`

- [ ] **Task:** Tratar falha do provedor sem fabricar resposta e permitir nova tentativa segura.
  - **Acceptance criteria:**
    - Falha preserva a mensagem do cliente, não cria mensagem `assistant` e retorna estado recuperável.
    - Retry usa a mesma conversa autorizada, não duplica indevidamente a mensagem original e não altera tenant.
  - **Feature tests:** `AgentProviderFailureTest` → cobre timeout/erro, ausência de resposta falsa, preservação e retry tenant-safe.
  - **Traces:** US-5.3, `agent_conversations`, `agent_conversation_messages`

### Phase 10.3: Chat do cliente

- [ ] **Task:** Expor endpoints tenant-aware para carregar histórico e enviar mensagem.
  - **Acceptance criteria:**
    - Somente sessão de cliente com tenant ativo e vínculo atual acessa a conversa; mensagens são retornadas ordenadas e sem metadados internos.
    - A implementação usa recursos do Inertia/Laravel AI compatíveis com as versões instaladas e não depende de Axios.
  - **Feature tests:** `CustomerConversationEndpointTest` → cobre autorização, histórico, envio, falha e isolamento.
  - **Traces:** US-5.1, US-5.2, US-4.3

- [ ] **Task:** Criar interface de chat persistente com estados de envio, resposta, erro e retry.
  - **Acceptance criteria:**
    - A página carrega histórico, diferencia mensagens do cliente/agente e mantém ordem após nova resposta.
    - Durante geração há feedback e envio duplicado é impedido; falha oferece retry sem perder a mensagem visível.
  - **Feature tests:** `CustomerChatPageTest` → valida props iniciais e proteção backend da conversa ativa.
  - **Design ref:** provisório — shell público, cards, inputs, buttons, skeletons e alerts da Phase 3.
  - **Traces:** US-5.1, US-5.2, US-5.3

---

## Phase 11: Administrative Conversation Browser and MVP Gate

**Goal:** Entregar consulta administrativa somente leitura das conversas e validar o MVP completo contra isolamento e qualidade. · **Depends on:** Phase 10 · **Covers:** US-6.1, US-6.2, Workflow 6, corte do MVP

### Phase 11.1: Consultas administrativas

- [ ] **Task:** Implementar listagem paginada de conversas do tenant administrativo.
  - **Acceptance criteria:**
    - Cada item identifica cliente, status e timestamps; busca/paginação permanecem filtradas pela empresa do usuário.
    - Mesmo e-mail em outro tenant não mistura conversa, pessoa ou contagem; ausência retorna estado vazio.
  - **Feature tests:** `AdminConversationIndexTest` → cobre listagem, paginação, vazio, e-mail compartilhado e isolamento entre empresas.
  - **Traces:** US-6.1, `agent_conversations`, `company_customers`, `customer_identities`, `conversation_statuses`

- [ ] **Task:** Implementar detalhe somente leitura com histórico ordenado.
  - **Acceptance criteria:**
    - O detalhe mostra cliente, status, timestamps e mensagens com papel, conteúdo e timestamps em ordem.
    - Conversa de outro tenant por URL/UUID é recusada sem expor conteúdo; não existem ações de edição/exclusão.
  - **Feature tests:** `AdminConversationShowTest` → cobre detalhe, ordenação, UUID cross-tenant e somente leitura.
  - **Traces:** US-6.2, US-1.2, `agent_conversations`, `agent_conversation_messages`, `message_roles`

### Phase 11.2: Telas administrativas

- [ ] **Task:** Criar página de listagem de conversas com estados de loading, vazio e paginação.
  - **Acceptance criteria:**
    - A página exibe somente os campos previstos e permite abrir o detalhe por rota tipada.
    - Layout é responsivo e não mistura conversas quando o cliente participa de várias empresas.
  - **Feature tests:** `AdminConversationPageTest` → valida props tenant-aware e paginação.
  - **Design ref:** provisório — AppLayout, cards/list, badges, skeletons e pagination patterns existentes.
  - **Traces:** US-6.1

- [ ] **Task:** Criar página de detalhe com cabeçalho da conversa e linha do tempo de mensagens.
  - **Acceptance criteria:**
    - Cliente, status e timestamps são apresentados; mensagens distinguem papéis e preservam ordem.
    - Não há controles de mutação e estados vazio/erro usam feedback visual consistente.
  - **Feature tests:** `AdminConversationDetailPageTest` → valida props autorizadas e ausência de payloads internos.
  - **Design ref:** provisório — AppLayout, cards, badges e message styling derivado dos componentes existentes.
  - **Traces:** US-6.2

### Phase 11.3: Gate final do MVP

- [ ] **Task:** Executar e corrigir a matriz completa de isolamento multi-tenant.
  - **Acceptance criteria:**
    - Testes cobrem administrador, conexão/sync, cada família Pipedrive, cliente, sessão, conversa e mensagem com ao menos dois tenants.
    - Alteração direta de IDs/UUIDs nunca retorna nem altera recurso de outro tenant.
  - **Feature tests:** `EndToEndTenantIsolationTest` → percorre Workflows 1–6 com dois tenants e identidade compartilhada.
  - **Traces:** US-1.2, US-2.1, US-2.2, US-2.4, US-4.3, US-5.1, US-6.1, US-6.2

- [ ] **Task:** Validar o fluxo feliz completo do MVP em teste de integração.
  - **Acceptance criteria:**
    - Cadastro cria tenant; OAuth fake conecta; sync fake importa; cliente recebe/usa link, seleciona empresa, conversa com provider fake; administrador consulta o histórico.
    - O teste não depende de Pipedrive, e-mail ou IA reais e comprova persistência esperada em cada etapa.
  - **Feature tests:** `MvpHappyPathTest` → executa o fluxo ponta a ponta e confirma os estados persistidos.
  - **Traces:** US-1.1, US-2.1, US-2.2, US-3.1, US-3.2, US-4.1, US-4.2, US-5.1, US-5.2, US-6.1, US-6.2

- [ ] **Task:** Rodar os gates finais de qualidade e corrigir regressões antes do corte do MVP.
  - **Acceptance criteria:**
    - Suite Pest, Pint, Larastan, ESLint, Prettier check, TypeScript check e build Vite passam via Sail.
    - Nenhum segredo está versionado, migrations rodam do zero e o código não implementa funcionalidades explicitamente fora do MVP.
  - **Feature tests:** suite completa → todos os testes de feature e integração permanecem verdes.
  - **Traces:** corte do MVP, Workflows 1–6

## Open Questions

- Qual provedor e qual modelo de IA serão configurados inicialmente? A decisão deve ser tomada antes da execução da Phase 10; o plano preserva a abstração e os testes com fake independentemente da escolha.
- `.spec/init/design/` não existe. Por decisão conservadora deste planejamento, as telas usam o starter kit existente como referência provisória; design manual futuro deve ser incorporado sem renumerar as fases existentes.
