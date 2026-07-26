# Lab Agent Seller — User Stories

<!-- inputs: project-description.md@sha256:c0b4c85fa4e7 -->

## Overview

O Lab Agent Seller é uma plataforma educacional multi-tenant de Agent as a Service para empresas conectarem seus dados do Pipedrive e disponibilizarem um agente global de atendimento e vendas aos seus clientes. O MVP separa rigorosamente dados sincronizados, contexto do agente, conversas e acessos por empresa.

**User Types:**
- **Administrador principal** - único usuário administrativo da empresa no MVP, responsável por cadastrar o tenant, conectar o Pipedrive, acompanhar os dados sincronizados e consultar conversas.
- **Cliente final** - pessoa identificada por um e-mail presente nos dados sincronizados de uma ou mais empresas, autorizada a conversar com o agente no tenant selecionado.

---

## 1. Cadastro e isolamento da empresa

### US-1.1: Cadastrar administrador e empresa
**As a** administrador principal
**I want to** cadastrar minha conta e minha empresa no mesmo fluxo
**So that** eu possa administrar um tenant isolado na plataforma

**Acceptance Criteria:**
- [ ] O cadastro público cria uma empresa e associa a ela exatamente um administrador principal.
- [ ] O administrador criado consegue autenticar-se pela área administrativa.
- [ ] A empresa passa a ser o tenant obrigatório dos dados administrativos criados posteriormente.
- [ ] O fluxo não oferece cadastro de administradores adicionais, papéis ou permissões granulares no MVP.
- [ ] Uma falha no cadastro não deixa uma empresa sem administrador nem um administrador sem empresa.

**Expected Result:** O administrador termina o cadastro autenticado e associado exclusivamente à empresa criada.

---

### US-1.2: Acessar somente o próprio tenant
**As a** administrador principal
**I want to** acessar apenas recursos pertencentes à minha empresa
**So that** os dados de outros tenants permaneçam protegidos

**Acceptance Criteria:**
- [ ] Configurações, integração CRM, dados sincronizados e conversas são sempre filtrados pela empresa do administrador autenticado.
- [ ] Identificadores de recursos de outra empresa não permitem leitura nem alteração desses recursos.
- [ ] Toda conexão CRM, dado sincronizado, cliente, conversa e mensagem possui associação obrigatória com uma empresa.
- [ ] Uma tentativa de acesso entre tenants é recusada sem expor o conteúdo do recurso solicitado.

**Expected Result:** O administrador visualiza e opera somente os recursos do próprio tenant.

---

## 2. Integração e sincronização do Pipedrive

### US-2.1: Conectar conta do Pipedrive
**As a** administrador principal
**I want to** autorizar minha conta do Pipedrive por OAuth
**So that** a plataforma possa ler os dados do CRM da minha empresa

**Acceptance Criteria:**
- [ ] O administrador autenticado consegue iniciar o fluxo OAuth a partir do próprio tenant.
- [ ] O retorno OAuth válido cria ou atualiza uma conexão associada exclusivamente à empresa do administrador.
- [ ] A autorização solicita apenas o acesso necessário para leitura no MVP.
- [ ] Um retorno negado, inválido ou com erro não cria uma conexão utilizável.
- [ ] A conexão de uma empresa não pode ser consultada nem utilizada por outra empresa.

**Expected Result:** A empresa possui uma conexão Pipedrive autorizada e pronta para a sincronização de leitura.

---

### US-2.2: Executar sincronização inicial
**As a** administrador principal
**I want to** sincronizar inicialmente os dados relevantes do Pipedrive
**So that** eu possa consultar uma representação local do CRM da minha empresa

**Acceptance Criteria:**
- [ ] A sincronização usa somente a conexão Pipedrive do tenant autenticado e não altera dados no CRM.
- [ ] Quando disponíveis e relevantes, são recuperados pipelines, etapas, campos personalizados, pessoas, organizações, negócios, produtos, atividades e usuários.
- [ ] Cada registro importado é persistido com associação obrigatória à empresa da conexão.
- [ ] Referências entre entidades sincronizadas não podem apontar para registros de outro tenant.
- [ ] Os dados sincronizados não são incluídos no contexto enviado ao agente no MVP.
- [ ] A conclusão registra um estado que permita ao administrador distinguir sucesso de falha.

**Expected Result:** Os dados relevantes do Pipedrive ficam armazenados localmente e isolados no tenant da empresa.

---

### US-2.3: Reexecutar sincronização com segurança
**As a** administrador principal
**I want to** reexecutar manualmente uma sincronização que falhou ou precisa ser atualizada
**So that** eu possa recuperar o processo sem duplicar ou corromper dados

**Acceptance Criteria:**
- [ ] O administrador pode iniciar novamente a sincronização da própria conexão.
- [ ] Reprocessar o mesmo registro atualiza sua representação local sem criar duplicatas funcionais.
- [ ] Uma falha informa o estado da sincronização ao administrador e permite nova tentativa manual.
- [ ] Dados parciais de uma tentativa não são associados a outro tenant.
- [ ] O MVP não executa sincronização contínua por webhook, agendamento ou periodicidade.

**Expected Result:** A sincronização pode ser repetida de forma idempotente e recuperável dentro do tenant correto.

---

### US-2.4: Consultar dados sincronizados
**As a** administrador principal
**I want to** visualizar os dados importados do meu Pipedrive
**So that** eu possa confirmar o conteúdo disponível na plataforma

**Acceptance Criteria:**
- [ ] A área administrativa apresenta somente dados sincronizados da empresa autenticada.
- [ ] As entidades disponíveis são identificáveis pelo tipo de dado importado.
- [ ] A ausência de um tipo de entidade não mistura nem exibe dados de outra empresa.
- [ ] A consulta é somente leitura em relação ao Pipedrive no MVP.

**Expected Result:** O administrador consegue inspecionar a representação local do CRM sem acesso cruzado entre tenants.

---

## 3. Autenticação do cliente por link mágico

### US-3.1: Solicitar link mágico
**As a** cliente final
**I want to** solicitar acesso informando meu e-mail
**So that** eu possa entrar quando estiver associado a uma empresa atendida

**Acceptance Criteria:**
- [ ] A plataforma procura o e-mail entre as pessoas sincronizadas de todas as empresas.
- [ ] Quando o e-mail não está associado a nenhuma empresa, nenhum link é enviado e a plataforma informa que não encontrou empresa associada.
- [ ] Quando o e-mail está associado a uma ou mais empresas, um link mágico de uso único é enviado ao endereço informado.
- [ ] O link gerado expira em 15 minutos por padrão.
- [ ] A validade do link é obtida de configuração centralizada e pode ser alterada sem modificar o fluxo.

**Expected Result:** Apenas um cliente elegível recebe uma credencial temporária para acessar a plataforma.

---

### US-3.2: Autenticar-se com link válido
**As a** cliente final
**I want to** usar um link mágico válido
**So that** eu possa iniciar uma sessão sem senha

**Acceptance Criteria:**
- [ ] Um link válido e ainda não utilizado autentica o cliente correspondente.
- [ ] O uso bem-sucedido invalida o link para qualquer tentativa posterior.
- [ ] A sessão criada dura 24 horas por padrão.
- [ ] A duração da sessão é obtida de configuração centralizada e pode ser alterada sem modificar o fluxo.
- [ ] As associações do e-mail com empresas são preservadas para determinar os tenants disponíveis após a autenticação.

**Expected Result:** O cliente fica autenticado em uma sessão temporária e segue para a definição do tenant da conversa.

---

### US-3.3: Recusar link inválido
**As a** cliente final
**I want to** receber uma resposta clara quando meu link não puder ser usado
**So that** eu não seja autenticado por uma credencial inválida

**Acceptance Criteria:**
- [ ] Um link expirado não autentica o cliente nem cria sessão.
- [ ] Um link já utilizado não autentica o cliente nem cria outra sessão.
- [ ] Um link inexistente ou adulterado não autentica o cliente.
- [ ] A recusa não concede acesso a tenants, conversas ou dados sincronizados.

**Expected Result:** Credenciais inválidas são recusadas sem criar uma sessão de cliente.

---

## 4. Seleção do tenant da conversa

### US-4.1: Usar automaticamente a única empresa associada
**As a** cliente final
**I want to** entrar diretamente na empresa quando houver uma única associação
**So that** eu possa começar a conversar sem uma escolha desnecessária

**Acceptance Criteria:**
- [ ] Após a autenticação, a plataforma determina as empresas associadas ao e-mail sincronizado do cliente.
- [ ] Quando existe exatamente uma empresa, ela é definida automaticamente como tenant ativo.
- [ ] Conversas e mensagens posteriores são obrigatoriamente associadas ao tenant ativo.
- [ ] A definição automática não concede acesso a dados administrativos ou dados sincronizados da empresa.

**Expected Result:** O único tenant autorizado torna-se o contexto ativo da conversa do cliente.

---

### US-4.2: Escolher entre empresas associadas
**As a** cliente final
**I want to** escolher uma empresa quando meu e-mail estiver em mais de um tenant
**So that** eu converse no contexto correto

**Acceptance Criteria:**
- [ ] Quando há mais de uma associação, a plataforma apresenta somente as empresas vinculadas ao e-mail autenticado.
- [ ] Nenhuma conversa pode ser iniciada antes da seleção de uma das empresas apresentadas.
- [ ] A escolha define o tenant ativo para todas as conversas e mensagens criadas durante aquele contexto.
- [ ] Uma empresa sem associação com o e-mail não pode ser selecionada por manipulação do identificador enviado.

**Expected Result:** O cliente seleciona um tenant autorizado, que passa a delimitar sua conversa.

---

### US-4.3: Impedir acesso a tenant não associado
**As a** cliente final
**I want to** ter meu acesso limitado às empresas vinculadas ao meu e-mail
**So that** conversas e dados de outros tenants permaneçam privados

**Acceptance Criteria:**
- [ ] Toda abertura ou retomada de conversa valida a associação atual entre o e-mail do cliente e a empresa.
- [ ] Uma tentativa de acessar conversa de empresa não associada é recusada sem expor o histórico.
- [ ] Identificadores de cliente, conversa ou mensagem não permitem atravessar o limite do tenant ativo.
- [ ] O cliente não recebe acesso aos dados sincronizados do CRM nem à área administrativa.

**Expected Result:** O cliente acessa somente conversas pertencentes a empresas autorizadas para seu e-mail.

---

## 5. Conversa com o agente global

### US-5.1: Iniciar ou retomar conversa persistente
**As a** cliente final
**I want to** iniciar ou continuar minha conversa na empresa selecionada
**So that** meu histórico seja preservado entre acessos

**Acceptance Criteria:**
- [ ] O MVP mantém uma conversa persistente por combinação de cliente e empresa.
- [ ] Na primeira interação da combinação, a plataforma cria a conversa com empresa, cliente, status e timestamps.
- [ ] Em acessos posteriores autorizados, a plataforma retoma a mesma conversa e seu histórico.
- [ ] A expiração da sessão exige nova autenticação, mas não apaga a conversa persistida.
- [ ] Uma conversa nunca é retomada em tenant diferente daquele ao qual pertence.

**Expected Result:** O cliente possui um histórico contínuo e isolado para cada empresa com a qual conversa.

---

### US-5.2: Trocar mensagens com o agente
**As a** cliente final
**I want to** enviar mensagens e receber respostas do agente
**So that** eu tenha uma experiência conversacional de atendimento e vendas

**Acceptance Criteria:**
- [ ] Cada entrada do cliente é persistida com conversa, tenant, papel `user`, conteúdo e timestamps.
- [ ] A plataforma envia a entrada ao único agente do MVP usando o System Prompt global versionado no código.
- [ ] A resposta do agente é persistida com conversa, tenant, papel `assistant`, conteúdo e timestamps.
- [ ] O histórico mantém a ordem das mensagens da conversa.
- [ ] O agente não recebe dados sincronizados do Pipedrive nem contexto personalizado por empresa.
- [ ] O agente não possui tools, actions, RAG ou capacidade de alterar o CRM.

**Expected Result:** A mensagem do cliente e a resposta do agente ficam registradas no histórico correto.

---

### US-5.3: Receber resposta sem acoplamento ao provedor
**As a** cliente final
**I want to** receber respostas do agente mesmo que a implementação de IA evolua
**So that** a experiência conversacional não dependa diretamente de um fornecedor específico

**Acceptance Criteria:**
- [ ] O fluxo de conversa usa uma abstração de provedor de IA, sem regras de negócio dependentes de uma implementação específica.
- [ ] O provedor e o modelo ativos são definidos de forma centralizada.
- [ ] Uma falha do provedor não registra uma resposta do agente como se tivesse sido concluída com sucesso.
- [ ] Uma falha ao gerar resposta preserva a mensagem já enviada pelo cliente e permite uma nova tentativa sem violar o tenant da conversa.
- [ ] A troca futura de provedor ou modelo não exige alterar as regras de isolamento, persistência ou autenticação.

**Expected Result:** O cliente utiliza um fluxo de conversa estável sobre uma integração de IA substituível.

---

## 6. Consulta administrativa das conversas

### US-6.1: Listar conversas da empresa
**As a** administrador principal
**I want to** listar as conversas do meu tenant
**So that** eu possa acompanhar os atendimentos realizados pelo agente

**Acceptance Criteria:**
- [ ] A listagem contém somente conversas associadas à empresa do administrador autenticado.
- [ ] Cada item permite identificar o cliente, o status e os timestamps persistidos da conversa.
- [ ] Conversas de outras empresas não aparecem, mesmo quando o cliente possui o mesmo e-mail em mais de um tenant.
- [ ] A ausência de conversas é apresentada sem consultar ou incorporar dados de outro tenant.

**Expected Result:** O administrador visualiza exclusivamente o conjunto de conversas da própria empresa.

---

### US-6.2: Visualizar histórico de uma conversa
**As a** administrador principal
**I want to** abrir o histórico persistido de uma conversa da minha empresa
**So that** eu possa analisar as mensagens trocadas com o agente

**Acceptance Criteria:**
- [ ] O detalhe apresenta o cliente, o status e os timestamps da conversa.
- [ ] O histórico apresenta as mensagens na ordem persistida, com papel, conteúdo e timestamps.
- [ ] Antes de exibir o detalhe, a plataforma valida que a conversa pertence ao tenant do administrador.
- [ ] Uma conversa de outro tenant não pode ser acessada por alteração direta de URL ou identificador.
- [ ] A consulta administrativa é somente leitura no MVP.

**Expected Result:** O administrador consulta o histórico completo de uma conversa autorizada sem exposição entre tenants.

---

## Open Questions

- Qual provedor e qual modelo de IA serão usados inicialmente? A escolha não deve remover a abstração de provedor definida para o MVP.

## Appendix: User Story Status

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| US-1.1 | Cadastrar administrador e empresa | High | Pending |
| US-1.2 | Acessar somente o próprio tenant | High | Pending |
| US-2.1 | Conectar conta do Pipedrive | High | Pending |
| US-2.2 | Executar sincronização inicial | High | Pending |
| US-2.3 | Reexecutar sincronização com segurança | High | Pending |
| US-2.4 | Consultar dados sincronizados | High | Pending |
| US-3.1 | Solicitar link mágico | High | Pending |
| US-3.2 | Autenticar-se com link válido | High | Pending |
| US-3.3 | Recusar link inválido | High | Pending |
| US-4.1 | Usar automaticamente a única empresa associada | High | Pending |
| US-4.2 | Escolher entre empresas associadas | High | Pending |
| US-4.3 | Impedir acesso a tenant não associado | High | Pending |
| US-5.1 | Iniciar ou retomar conversa persistente | High | Pending |
| US-5.2 | Trocar mensagens com o agente | High | Pending |
| US-5.3 | Receber resposta sem acoplamento ao provedor | High | Pending |
| US-6.1 | Listar conversas da empresa | High | Pending |
| US-6.2 | Visualizar histórico de uma conversa | High | Pending |
