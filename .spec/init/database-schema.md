# Lab Agent Seller — Database Schema

<!-- inputs: project-description.md@sha256:c0b4c85fa4e7 user-stories.md@sha256:cd886a1f9c60 -->

## Overview

O modelo organiza o isolamento multi-tenant em torno de **companies**, com um **user** administrativo por empresa no MVP. A integração é representada por **crm_connections**, execuções rastreáveis em **crm_sync_runs** e tabelas explícitas para as entidades importadas do Pipedrive. A autenticação do cliente separa a identidade global por e-mail em **customer_identities** do vínculo tenant-local em **company_customers**, permitindo que o mesmo endereço esteja associado a várias empresas sem compartilhar seus dados.

O schema segue as convenções do Laravel 13 com Eloquent: tabelas plurais em `snake_case`, chaves primárias `bigint` incrementais nas tabelas da aplicação e timestamps `created_at`/`updated_at`. As tabelas de conversas já instaladas pelo Laravel AI preservam IDs `varchar(36)` e campos de compatibilidade do pacote. Estados e papéis controlados pela aplicação usam lookup tables; não há soft deletes porque nenhum fluxo de exclusão foi definido para o MVP.

## Schema (DBML)

```dbml
// Lookup tables first, then domain tables, then pivots.

Table crm_connection_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  description text [null]
  is_active boolean [not null, default: true]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table crm_sync_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  description text [null]
  is_active boolean [not null, default: true]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table conversation_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  description text [null]
  is_active boolean [not null, default: true]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table message_roles {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  description text [null]
  is_active boolean [not null, default: true]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table companies {
  id bigint [pk, increment]
  name varchar [not null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table users {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, unique, not null]
  name varchar [not null]
  email varchar [unique, not null]
  email_verified_at timestamp [null]
  password varchar [not null]
  remember_token varchar [null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table crm_connections {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, unique, not null]
  status_id bigint [ref: > crm_connection_statuses.id, not null]
  provider varchar [not null, default: 'pipedrive']
  external_company_id varchar [null]
  access_token text [not null]
  refresh_token text [null]
  token_expires_at timestamp [null]
  authorized_at timestamp [null]
  last_error text [null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table crm_sync_runs {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  status_id bigint [ref: > crm_sync_statuses.id, not null]
  started_at timestamp [null]
  finished_at timestamp [null]
  error_message text [null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, created_at)
    (crm_connection_id, status_id)
  }
}

Table pipedrive_pipelines {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  pipedrive_id bigint [not null]
  name varchar [not null]
  display_order integer [null]
  is_active boolean [not null, default: true]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, pipedrive_id) [unique]
  }
}

Table pipedrive_stages {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  pipedrive_pipeline_id bigint [ref: > pipedrive_pipelines.id, not null]
  pipedrive_id bigint [not null]
  name varchar [not null]
  display_order integer [null]
  is_active boolean [not null, default: true]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, pipedrive_id) [unique]
    (pipedrive_pipeline_id, display_order)
  }
}

Table pipedrive_custom_fields {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  pipedrive_id bigint [null]
  pipedrive_key varchar [not null]
  entity_type varchar [not null]
  field_type varchar [not null]
  name varchar [not null]
  is_active boolean [not null, default: true]
  options_payload jsonb [null]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, entity_type, pipedrive_key) [unique]
  }
}

Table pipedrive_users {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  pipedrive_id bigint [not null]
  name varchar [not null]
  email varchar [null]
  is_active boolean [not null, default: true]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, pipedrive_id) [unique]
  }
}

Table pipedrive_organizations {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  owner_pipedrive_user_id bigint [ref: > pipedrive_users.id, null]
  pipedrive_id bigint [not null]
  name varchar [not null]
  address text [null]
  is_active boolean [not null, default: true]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, pipedrive_id) [unique]
  }
}

Table pipedrive_people {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  pipedrive_organization_id bigint [ref: > pipedrive_organizations.id, null]
  owner_pipedrive_user_id bigint [ref: > pipedrive_users.id, null]
  pipedrive_id bigint [not null]
  name varchar [not null]
  is_active boolean [not null, default: true]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, pipedrive_id) [unique]
  }
}

Table pipedrive_person_emails {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  pipedrive_person_id bigint [ref: > pipedrive_people.id, not null]
  email varchar [not null]
  label varchar [null]
  is_primary boolean [not null, default: false]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, email)
    (pipedrive_person_id, email) [unique]
  }
}

Table pipedrive_deals {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  pipedrive_pipeline_id bigint [ref: > pipedrive_pipelines.id, null]
  pipedrive_stage_id bigint [ref: > pipedrive_stages.id, null]
  pipedrive_person_id bigint [ref: > pipedrive_people.id, null]
  pipedrive_organization_id bigint [ref: > pipedrive_organizations.id, null]
  owner_pipedrive_user_id bigint [ref: > pipedrive_users.id, null]
  pipedrive_id bigint [not null]
  title varchar [not null]
  value decimal(15,2) [null]
  currency varchar(3) [null]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, pipedrive_id) [unique]
    (company_id, pipedrive_pipeline_id, pipedrive_stage_id)
  }
}

Table pipedrive_products {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  pipedrive_id bigint [not null]
  name varchar [not null]
  code varchar [null]
  unit_price decimal(15,2) [null]
  currency varchar(3) [null]
  is_active boolean [not null, default: true]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, pipedrive_id) [unique]
  }
}

Table pipedrive_activities {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  crm_connection_id bigint [ref: > crm_connections.id, not null]
  pipedrive_deal_id bigint [ref: > pipedrive_deals.id, null]
  pipedrive_person_id bigint [ref: > pipedrive_people.id, null]
  pipedrive_organization_id bigint [ref: > pipedrive_organizations.id, null]
  owner_pipedrive_user_id bigint [ref: > pipedrive_users.id, null]
  pipedrive_id bigint [not null]
  subject varchar [not null]
  due_at timestamp [null]
  is_done boolean [not null, default: false]
  raw_payload jsonb [not null]
  synced_at timestamp [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, pipedrive_id) [unique]
  }
}

Table customer_identities {
  id bigint [pk, increment]
  email varchar [unique, not null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table customer_magic_links {
  id bigint [pk, increment]
  customer_identity_id bigint [ref: > customer_identities.id, not null]
  token_hash varchar [unique, not null]
  expires_at timestamp [not null]
  used_at timestamp [null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (customer_identity_id, expires_at)
  }
}

Table customer_sessions {
  id varchar(255) [pk]
  customer_identity_id bigint [ref: > customer_identities.id, not null]
  active_company_id bigint [ref: > companies.id, null]
  token_hash varchar [unique, not null]
  ip_address varchar(45) [null]
  user_agent text [null]
  expires_at timestamp [not null]
  last_activity_at timestamp [not null]
  revoked_at timestamp [null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (customer_identity_id, expires_at)
    (active_company_id, customer_identity_id)
  }
}

Table agent_conversations {
  id varchar(36) [pk]
  company_id bigint [ref: > companies.id, not null]
  company_customer_id bigint [ref: > company_customers.id, not null]
  status_id bigint [ref: > conversation_statuses.id, not null]
  participant_type varchar [null]
  participant_id bigint [null]
  title varchar [not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, company_customer_id) [unique]
    (participant_type, participant_id, updated_at)
  }
}

Table agent_conversation_messages {
  id varchar(36) [pk]
  conversation_id varchar(36) [ref: > agent_conversations.id, not null]
  company_id bigint [ref: > companies.id, not null]
  message_role_id bigint [ref: > message_roles.id, not null]
  participant_type varchar [null]
  participant_id bigint [null]
  agent varchar [not null]
  role varchar(25) [not null]
  content text [not null]
  attachments text [not null]
  tool_calls text [not null]
  tool_results text [not null]
  usage text [not null]
  meta text [not null]
  approval_state text [null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (conversation_id, participant_type, participant_id, updated_at)
    (participant_type, participant_id)
    (company_id, conversation_id)
  }
}

Table company_customers {
  id bigint [pk, increment]
  company_id bigint [ref: > companies.id, not null]
  customer_identity_id bigint [ref: > customer_identities.id, not null]
  pipedrive_person_id bigint [ref: > pipedrive_people.id, not null]
  pipedrive_person_email_id bigint [ref: > pipedrive_person_emails.id, not null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (company_id, customer_identity_id) [unique]
    (company_id, pipedrive_person_email_id) [unique]
  }
}

Table pipedrive_deal_products {
  pipedrive_deal_id bigint [ref: > pipedrive_deals.id, not null]
  pipedrive_product_id bigint [ref: > pipedrive_products.id, not null]
  quantity decimal(15,4) [not null, default: 1]
  unit_price decimal(15,2) [null]
  discount_percentage decimal(5,2) [null]
  created_at timestamp [null]
  updated_at timestamp [null]

  indexes {
    (pipedrive_deal_id, pipedrive_product_id) [pk]
  }
}
```

## Relationships

- Uma **company** possui exatamente um **user** administrador no MVP, garantido pela unicidade de `users.company_id`.
- Uma **company** possui uma **crm_connection**, e cada conexão pertence a um **crm_connection_status**.
- Uma **crm_connection** possui muitas **crm_sync_runs**, e cada execução pertence a um **crm_sync_status** e à mesma empresa da conexão.
- Uma **company** possui muitos pipelines, etapas, campos personalizados, usuários, organizações, pessoas, negócios, produtos e atividades sincronizados do Pipedrive.
- Um **pipedrive_pipeline** possui muitas **pipedrive_stages**.
- Uma **pipedrive_organization** pode possuir muitas **pipedrive_people** e tem opcionalmente um usuário do Pipedrive como responsável.
- Uma **pipedrive_person** possui muitos **pipedrive_person_emails** e pode pertencer a uma organização e a um responsável do Pipedrive.
- Um **pipedrive_deal** pode pertencer a pipeline, etapa, pessoa, organização e usuário responsável sincronizados.
- **pipedrive_deals** e **pipedrive_products** são muitos-para-muitos por **pipedrive_deal_products**.
- Uma **pipedrive_activity** pode estar associada a negócio, pessoa, organização e usuário responsável sincronizados.
- Uma **customer_identity** possui um e-mail global único e pode vincular-se a muitas empresas por **company_customers**.
- Uma **company_customer** pertence a uma empresa, uma identidade global, uma pessoa sincronizada e o e-mail sincronizado que autorizou o vínculo.
- Uma **customer_identity** possui muitos **customer_magic_links** e muitas **customer_sessions**.
- Uma **customer_session** pode apontar para uma empresa ativa, sempre validada pela aplicação contra **company_customers**.
- Uma **company_customer** possui no máximo uma **agent_conversation** persistente por empresa no MVP.
- Uma **agent_conversation** pertence a uma empresa e a um **conversation_status**, e possui muitas **agent_conversation_messages**.
- Uma **agent_conversation_message** pertence a uma conversa, à mesma empresa e a um **message_role**.

## Lookup Table Seeds

- **crm_connection_statuses** → `pending`, `active`, `failed`, `revoked`, `expired`.
- **crm_sync_statuses** → `pending`, `running`, `succeeded`, `failed`.
- **conversation_statuses** → `active`. Outros estados dependem de um futuro fluxo explícito de encerramento.
- **message_roles** → `user`, `assistant`, `system`.

## Notes & Conventions

- O isolamento multi-tenant é materializado por `company_id` nas tabelas pertencentes ao tenant, inclusive nas mensagens. As regras da aplicação devem validar que todas as referências redundantes apontam para registros da mesma empresa.
- `users.company_id` é único no MVP para impor um administrador principal por empresa. A evolução para múltiplos administradores exigirá remover essa unicidade e introduzir o modelo de papéis decidido nessa fase futura.
- `customer_identities` não é um tenant: ela representa somente a identidade autenticável por e-mail. O cliente pertencente ao tenant é `company_customers`, que impede o compartilhamento de pessoas, conversas ou mensagens entre empresas.
- `pipedrive_person_emails` normaliza todos os e-mails de uma pessoa; cada e-mail elegível pode originar sua identidade e vínculo em `company_customers`.
- Todas as tabelas sincronizadas carregam `company_id`, `crm_connection_id`, o identificador externo e `raw_payload`. A chave única `(company_id, pipedrive_id)` torna a reexecução da sincronização idempotente.
- Os campos `entity_type`, `field_type` e `label` reproduzem metadados externos e extensíveis do Pipedrive, não categorias fechadas da aplicação. Os valores de campos personalizados permanecem em `raw_payload`; suas definições ficam em `pipedrive_custom_fields`.
- Tokens OAuth e tokens de autenticação devem ser armazenados cifrados ou como hash conforme sua necessidade de recuperação: OAuth precisa ser recuperável pela aplicação; links mágicos e sessões usam apenas `token_hash`.
- A validade padrão de 15 minutos do link mágico e de 24 horas da sessão pertence à configuração da aplicação, não a tabelas. `expires_at` registra o valor calculado para cada emissão.
- `used_at`, `expires_at` e `revoked_at` tornam os estados de links e sessões derivados, evitando lookup tables para estados calculáveis.
- `agent_conversations` e `agent_conversation_messages` preservam nomes, chaves UUID e colunas exigidas pela migration instalada do Laravel AI. `company_id`, `company_customer_id`, `status_id` e `message_role_id` são extensões do domínio.
- `agent_conversation_messages.role` é mantido como espelho denormalizado obrigatório para compatibilidade com o Laravel AI; `message_role_id` é a referência categórica canônica. A aplicação deve garantir que ambos representem o mesmo papel.
- `participant_type`, `participant_id`, `agent`, `attachments`, `tool_calls`, `tool_results`, `usage`, `meta` e `approval_state` permanecem porque pertencem ao contrato do Laravel AI. Tools, aprovações e anexos não fazem parte do comportamento do MVP.
- O System Prompt global, o provedor e o modelo do agente são versionados/configurados no código e não são persistidos neste schema.
- Os dados sincronizados não se relacionam com mensagens nem são consultados para compor o contexto do agente no MVP.
- Nenhuma tabela de domínio usa soft delete: não existe história de exclusão ou política de retenção definida. A remoção futura deve ser especificada antes de adicionar `deleted_at`.
- As tabelas existentes de infraestrutura do Laravel (`password_reset_tokens`, `sessions`, `cache`, `jobs`, `failed_jobs`, `job_batches` e `passkeys`) continuam regidas por suas migrations e não representam conceitos do domínio neste DBML. `sessions` atende a autenticação administrativa; sessões de clientes são isoladas em `customer_sessions`.
- Índices compostos iniciados por `company_id` suportam escopo tenant-local. As migrations devem ainda aplicar foreign keys e políticas de deleção de forma explícita, sem cascatas entre tenants.
- **Agent as a Service (AAS)** — não persistido: é a arquitetura do produto, materializada pelos serviços e entidades do domínio.
- **Contexto do agente** — não persistido: é montado em runtime e, no MVP, exclui intencionalmente os dados sincronizados.
- **Agente global** — não persistido: o System Prompt fica versionado no código e provedor/modelo ficam em configuração centralizada.

## Open Questions

- Qual provedor e qual modelo de IA serão configurados inicialmente? A decisão não altera as tabelas propostas.
