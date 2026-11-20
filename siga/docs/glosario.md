# Glosario Español-Inglés — SIGA: Módulo de Solicitudes Estudiantiles

Glosario de terminología de dominio para el proyecto SIGA (Sistema de Gestión Académica), elaborado como parte de la tarea 9.3 del Plan de Trabajo. Organiza los términos por contexto delimitado (bounded context) y añade secciones transversales para arquitectura hexagonal/DDD y control de acceso basado en roles (RBAC).

---

## 1. Términos generales de arquitectura (DDD / Hexagonal)

Términos transversales a todos los contextos delimitados, usados en la organización interna de las capas del código (Domain, Application, Infrastructure, Presentation) y validados, entre otras cosas, por `HexagonalBoundaryTest`.

- **Contexto Delimitado** (Bounded Context) — Frontera lógica y de lenguaje dentro del sistema (p. ej. `StudentRequests`, `AcademicCatalog`, `IdentityAccess`, `Shared`) que agrupa un modelo de dominio coherente y evita ambigüedades de términos compartidos entre módulos.
- **Capa de Dominio** (Domain Layer) — Capa que contiene las reglas de negocio puras (entidades, objetos de valor, servicios de dominio); no debe depender de frameworks como Illuminate, Livewire o Flux, condición verificada por `HexagonalBoundaryTest`.
- **Capa de Aplicación** (Application Layer) — Capa que orquesta casos de uso, coordinando entidades de dominio y puertos (repositorios) sin contener lógica de negocio propia ni detalles de infraestructura.
- **Capa de Infraestructura** (Infrastructure Layer) — Capa que implementa los detalles técnicos concretos (adaptadores Eloquent, colas, almacenamiento de archivos) que satisfacen los puertos definidos en el dominio/aplicación.
- **Capa de Presentación** (Presentation Layer) — Capa de interfaz de usuario construida con Livewire/Flux (p. ej. `StudentRequestComponent`) que consume los casos de uso de la capa de aplicación.
- **Caso de Uso** (Use Case) — Clase de la capa de aplicación que representa una acción de negocio concreta e invocable (p. ej. `EnsureEstimatedResolutionDateUseCase`, `RecordStudentRequestStatusHistoryUseCase`).
- **Entidad** (Entity) — Objeto de dominio con identidad propia y ciclo de vida (p. ej. una `StudentRequest` identificada por su ID, cuyo estado cambia con el tiempo pero conserva su identidad).
- **Objeto de Valor** (Value Object) — Objeto de dominio inmutable definido solo por sus atributos, sin identidad propia (p. ej. un rango de fechas de resolución o un tamaño de archivo validado).
- **Agregado** (Aggregate) — Conjunto de entidades y objetos de valor tratados como una unidad transaccional consistente, con una entidad raíz que controla el acceso al resto (p. ej. `StudentRequest` como raíz que agrupa sus adjuntos e historial de estados).
- **Raíz de Agregado** (Aggregate Root) — Entidad principal de un agregado a través de la cual se accede y modifica el resto de sus componentes internos, garantizando invariantes de negocio.
- **Repositorio** (Repository) — Abstracción que encapsula el acceso a la persistencia de un agregado; en este proyecto se define como un puerto (interfaz) en el dominio y se implementa mediante un adaptador Eloquent en infraestructura.
- **Puerto** (Port) — Interfaz definida en el dominio o la aplicación que describe una capacidad requerida (p. ej. persistir solicitudes) sin especificar cómo se implementa.
- **Adaptador** (Adapter) — Implementación concreta de un puerto que conecta el dominio/aplicación con tecnología externa (base de datos, sistema de archivos, etc.), típica del patrón de arquitectura hexagonal (puertos y adaptadores).
- **Servicio de Dominio** (Domain Service) — Lógica de negocio que no pertenece naturalmente a una sola entidad, expresada como servicio dentro de la capa de dominio (p. ej. `BusinessDayCalculator`, `CourseNameNormalizer`).
- **Motor de Reglas** (Rules Engine) — Componente de dominio que evalúa un conjunto de reglas de negocio configurables sobre una solicitud (p. ej. `WaiverEvaluationEngine`, el motor de reglas de levantamiento de requisitos).
- **Prueba de Frontera Hexagonal** (Hexagonal Boundary Test) — Prueba de arquitectura (`HexagonalBoundaryTest`) que garantiza la pureza de la capa de dominio, impidiendo importaciones de Illuminate, Livewire o Flux dentro de ella.
- **Criterio de Aceptación** (Acceptance Criteria) — Condición verificable que define cuándo una funcionalidad se considera completa; en este proyecto se codifican con etiquetas como ES-01 a ES-04.

---

## 2. Contexto: StudentRequests (Solicitudes Estudiantiles)

Contexto delimitado central del módulo, que gestiona el ciclo de vida de las solicitudes que presentan los estudiantes.

- **Solicitud Estudiantil** (Student Request) — Entidad central del contexto (`StudentRequest`), que representa una petición formal de un estudiante ante la institución, con un tipo, un estado y un flujo de resolución.
- **Levantamiento de Requisito** (Waiver / Prerequisite Override) — Tipo de solicitud estudiantil mediante la cual un estudiante pide ser eximido de un prerrequisito académico para poder cursar una asignatura.
- **Convalidación de Créditos** (Transfer Credit) — Tipo de solicitud estudiantil mediante la cual un estudiante pide que créditos cursados en otra institución u otro programa sean reconocidos dentro de su plan de estudios actual.
- **Motor de Evaluación de Levantamientos** (Waiver Evaluation Engine) — Componente de dominio (`WaiverEvaluationEngine`) que aplica reglas de negocio para determinar si una solicitud de levantamiento de requisito procede, según el criterio de aceptación ES-01.
- **Precedente de Convalidación** (Transfer Credit Precedent) — Entidad (`TransferCreditPrecedent`) que registra convalidaciones aprobadas previamente, usada como referencia para evaluar nuevas solicitudes de convalidación de créditos (ES-02).
- **Normalizador de Nombres de Curso** (Course Name Normalizer) — Servicio de dominio (`CourseNameNormalizer`) que estandariza los nombres de instituciones y cursos para permitir el emparejamiento (matching) confiable con precedentes de convalidación.
- **Fecha Estimada de Resolución** (Estimated Resolution Date) — Fecha calculada automáticamente en la que se espera resolver una solicitud, obtenida mediante `EnsureEstimatedResolutionDateUseCase` contando 5 días hábiles (ES-03).
- **Calculadora de Días Hábiles** (Business Day Calculator) — Servicio de dominio (`BusinessDayCalculator`) que calcula fechas excluyendo fines de semana y días feriados, usado para determinar plazos de resolución.
- **Día Hábil** (Business Day) — Día laborable considerado válido para el cómputo de plazos, excluyendo sábados, domingos y feriados.
- **Dictamen de Resolución** (Resolution Statement) — Entidad (`ResolutionStatement`) que representa la decisión formal emitida sobre una solicitud (aprobación, rechazo u observación).
- **Adjunto** (Attachment) — Entidad (`Attachment`) que representa un archivo cargado junto a una solicitud, sujeto a validaciones de tipo MIME y tamaño máximo permitido.
- **Tipo MIME** (MIME Type) — Clasificación del formato de un archivo adjunto (p. ej. `application/pdf`), utilizada para restringir qué tipos de archivo pueden subirse como evidencia de una solicitud.
- **Tamaño Máximo** (Max Size) — Límite de tamaño en bytes/megabytes que un archivo adjunto no puede exceder, validado antes de aceptar la carga.
- **Historial de Estado de la Solicitud** (Student Request Status History) — Registro de auditoría (`StudentRequestStatusHistory`) que conserva cada cambio de estado que ha sufrido una solicitud a lo largo del tiempo.
- **Caso de Uso de Registro de Historial** (Record Status History Use Case) — Caso de uso (`RecordStudentRequestStatusHistoryUseCase`) encargado de crear una entrada en el historial cada vez que cambia el estado de una solicitud.
- **Política de Solicitudes Estudiantiles** (Student Request Policy) — Política de autorización de Laravel (`StudentRequestPolicy`) que define qué roles pueden ver, crear, actualizar o exportar solicitudes.
- **Bandeja** (Inbox) — Vista central donde se listan las solicitudes pendientes o en curso para su revisión, implementada mediante `StudentRequestComponent`.
- **Bandeja de Revisión** (Review Inbox / "inbox" tab) — Pestaña específica de la bandeja dedicada a las solicitudes que requieren revisión por parte del personal autorizado.
- **Actualización Automática** (Auto-Refresh / `wire:poll`) — Mecanismo de Livewire (`wire:poll`) que refresca periódicamente el contenido de la bandeja sin que el usuario recargue la página manualmente.
- **Filtro** (Filter) — Criterio aplicado sobre la bandeja para acotar las solicitudes mostradas (por estado, tipo, fecha, etc.), parte del alcance funcional de ES-04.
- **Bandeja Centralizada** (Centralized Inbox) — Requisito funcional (ES-04) de que exista una única bandeja que agregue solicitudes visibles según el rol del usuario autenticado, con filtros y autorización basada en roles.
- **Estado de la Solicitud** (Request Status) — Valor que representa la etapa actual del ciclo de vida de una solicitud (p. ej. pendiente, en revisión, resuelta), cuyos cambios quedan registrados en el historial de estado.

---

## 3. Contexto: IdentityAccess (Identidad y Acceso) — RBAC

Contexto delimitado que gestiona la identidad de los usuarios y el control de acceso basado en roles (RBAC), implementado mediante el trait propio `HasRolesAndPermissions` (no se usa el paquete spatie/laravel-permission).

- **Control de Acceso Basado en Roles** (Role-Based Access Control, RBAC) — Modelo de autorización en el que los permisos se asignan a roles y estos roles se asignan a los usuarios, en lugar de asignar permisos individualmente a cada usuario.
- **Trait de Roles y Permisos** (HasRolesAndPermissions Trait) — Trait propio del proyecto que dota a los modelos de usuario de capacidades de gestión de roles y permisos, implementado sin depender de librerías externas de RBAC.
- **Rol** (Role) — Conjunto nombrado de permisos que se asigna a un usuario para definir qué acciones puede realizar dentro del sistema (p. ej. Superadmin, Admin, Coordinadora).
- **Permiso** (Permission) — Autorización granular para realizar una acción específica sobre un módulo del sistema, base del modelo RBAC del proyecto.
- **Módulo** (Module) — Área funcional del sistema (p. ej. el módulo de Solicitudes Estudiantiles) sobre la cual se definen permisos y acciones controlables.
- **Acción** (Action) — Operación concreta permitida o restringida dentro de un módulo (p. ej. ver, crear, actualizar, exportar), que junto con el módulo compone la unidad mínima de un permiso.
- **Superadmin** (Superadmin) — Rol con el nivel más alto de privilegios en el sistema, con acceso irrestricto a todos los módulos y acciones.
- **Administrador/a** (Admin) — Rol con privilegios administrativos amplios sobre el sistema, aunque de menor alcance que Superadmin.
- **Estudiante** (Student) — Rol asignado a los usuarios que presentan solicitudes estudiantiles; tiene acceso limitado a sus propias solicitudes.
- **Coordinadora/Coordinador** (Coordinator) — Rol del personal académico responsable de coordinar y revisar solicitudes estudiantiles dentro de su ámbito.
- **Docencia** (Teaching / Faculty Affairs Staff) — Rol asignado al personal de asuntos docentes que interviene en la revisión o resolución de ciertas solicitudes.
- **Comisión Técnica** (Comisión / Technical Committee) — Rol que representa a un cuerpo colegiado responsable de evaluar y resolver solicitudes que requieren un dictamen colectivo, como levantamientos de requisito o convalidaciones complejas.
- **Autorización** (Authorization) — Proceso de verificar si un usuario autenticado tiene permiso para ejecutar una acción determinada, aplicado en este proyecto mediante políticas de Laravel como `StudentRequestPolicy`.
- **Autenticación** (Authentication) — Proceso de verificar la identidad de un usuario (inicio de sesión), distinto de la autorización, gestionado dentro del contexto IdentityAccess.

---

## 4. Contexto: AcademicCatalog (Catálogo Académico)

Contexto delimitado que provee la información académica de referencia (cursos, planes de estudio, instituciones) usada por las solicitudes estudiantiles, en particular para la evaluación de convalidaciones.

- **Catálogo Académico** (Academic Catalog) — Contexto delimitado que centraliza la información estructural de la oferta académica (cursos, prerrequisitos, planes de estudio) consultada por otros contextos como `StudentRequests`.
- **Curso** (Course) — Unidad académica (asignatura) dentro del catálogo, referenciada tanto en solicitudes de levantamiento de requisito como en convalidaciones de créditos.
- **Prerrequisito** (Prerequisite) — Condición académica previa exigida para inscribir un curso, cuyo cumplimiento puede ser objeto de una solicitud de levantamiento de requisito.
- **Institución de Origen** (Origin Institution) — Institución externa en la que un estudiante cursó materias que ahora solicita convalidar; su nombre se normaliza mediante `CourseNameNormalizer` para el emparejamiento con precedentes.
- **Plan de Estudios** (Study Plan / Curriculum) — Estructura curricular que define los cursos y prerrequisitos correspondientes a un programa académico, referenciada al evaluar levantamientos y convalidaciones.

---

## 5. Contexto: Shared (Compartido)

Contexto delimitado que agrupa utilidades, objetos de valor y servicios genéricos reutilizados por los demás contextos delimitados, evitando duplicación de lógica transversal.

- **Contexto Compartido** (Shared Context / Shared Kernel) — Contexto delimitado que contiene elementos de dominio genéricos (objetos de valor comunes, servicios utilitarios) usados por múltiples contextos sin pertenecer exclusivamente a ninguno.
- **Núcleo Compartido** (Shared Kernel) — Patrón DDD en el que varios contextos delimitados acuerdan compartir un subconjunto reducido y estable del modelo de dominio, correspondiente en este proyecto al contexto `Shared`.
- **Utilidad de Dominio** (Domain Utility) — Servicio o función de propósito general dentro del contexto compartido (p. ej. cálculo de fechas, normalización de texto) que no pertenece a la lógica de negocio específica de un solo contexto.

---

## 6. Notas de uso

- Los prefijos **ES-01** a **ES-04** identifican criterios de aceptación específicos: ES-01 (reglas de levantamiento), ES-02 (precedentes de convalidación), ES-03 (fecha estimada de resolución/días hábiles) y ES-04 (bandeja centralizada con autorización por roles y filtros).
- Los nombres de clases e identificadores de código se mantienen en inglés (convención del proyecto), mientras que el vocabulario de negocio con el que interactúan los usuarios finales (estudiantes, coordinadoras, comisión técnica) se expresa en español; este glosario sirve como puente de traducción entre ambos.
