# KeyNography — Despliegue Multi-Cloud (AWS + Azure)

> **Versión:** 1.0 · **Estado:** Propuesta de arquitectura
> **Alcance:** Aplicación Laravel 12 + API FastAPI + MySQL + Pusher, desplegada en AWS y Azure con CI/CD vía GitHub Actions.

---

## Tabla de contenidos

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Arquitectura de alto nivel](#2-arquitectura-de-alto-nivel)
3. [Modelo C4](#3-modelo-c4)
   - [3.1 Diagrama de contexto](#31-diagrama-de-contexto)
   - [3.2 Diagrama de contenedores](#32-diagrama-de-contenedores)
4. [Despliegue en AWS](#4-despliegue-en-aws)
5. [Despliegue en Azure](#5-despliegue-en-azure)
6. [DNS, balanceo y alta disponibilidad](#6-dns-balanceo-y-alta-disponibilidad)
7. [CI/CD completo](#7-cicd-completo)
   - [7.1 Estrategia de ramas y entornos](#71-estrategia-de-ramas-y-entornos)
   - [7.2 Pipeline de la aplicación](#72-pipeline-de-la-aplicacin)
   - [7.3 Pipeline de la API](#73-pipeline-de-la-api)
   - [7.4 Flujo de despliegue y rollback](#74-flujo-de-despliegue-y-rollback)
8. [Flujos de datos y secuencias](#8-flujos-de-datos-y-secuencias)
9. [Observabilidad y operaciones](#9-observabilidad-y-operaciones)
10. [Continuidad de negocio y failover](#10-continuidad-de-negocio-y-failover)
11. [Costos estimados (créditos académicos)](#11-costos-estimados-crditos-acadmicos)
12. [Roadmap de implementación](#12-roadmap-de-implementacin)

> Los diagramas están en **PlantUML**. Para verlos renderizados usa la extensión de PlantUML de VS Code, o pégalos en [plantuml.com](https://www.plantuml.com/plantuml).

---

## 1. Resumen ejecutivo

KeyNography se despliega de forma **multi-cloud activa-activa lógica**: la misma aplicación corre en **AWS** y en **Azure** de forma independiente, cada nube con su propia base de datos y almacenamiento. Un DNS con *failover routing* (Route 53) envía el tráfico al proveedor primario (AWS) y conmutaría automáticamente a Azure si AWS deja de responder.

| Componente | AWS (primario) | Azure (secundario / DR) |
|---|---|---|
| Frontend + Backend Laravel | Elastic Beanstalk (Docker) | Azure Container Apps |
| API FastAPI | Elastic Beanstalk (Docker) | Azure Container Apps |
| Base de datos MySQL | RDS `db.t3.micro` | Azure Database for MySQL Flexible `B1ms` |
| Archivos generados (audio/imagen) | S3 | Azure Blob Storage |
| Balanceo / TLS | ALB (incluido en EB) + ACM | Ingress gestionado de Container Apps |
| Realtime | Pusher (SaaS, común a ambos) | Pusher (SaaS, común a ambos) |
| DNS / Failover | Route 53 (health checks) | Route 53 health checks |

**Decisión clave:** se reutilizan los `Dockerfile` existentes (`webdevops/php-nginx` para Laravel, imagen Python para FastAPI), por lo que el despliegue es "build peak → push registry → release", sin reescribir la aplicación.

---

## 2. Arquitectura de alto nivel

```plantuml
@startuml Arquitectura Alto Nivel - KeyNography MultiCloud
!pragma layout smetana
top to bottom direction
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 13
skinparam roundcorner 14
skinparam nodesep 45
skinparam ranksep 55
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam actor {
  BackgroundColor #238636
  BorderColor #2EA043
  FontColor #FFFFFF
}
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam component<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam component<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam database<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam database<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam storage<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam storage<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam component<<ext>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}
skinparam cloud {
  BackgroundColor #6E40C9
  BorderColor #A371F7
  FontColor #D2A8FF
}

actor "Estudiante / Docente" as user
component "Route 53 - Failover + Health Checks" as r53 <<ext>>
cloud "Pusher - Canales realtime" as pusher

node "AWS - Produccion primaria" {
  component "ALB (Application Load Balancer)" as alb <<aws>>
  component "Elastic Beanstalk - Laravel (PHP 8.3 + Nginx)" as ebapp <<aws>>
  component "Elastic Beanstalk - FastAPI (Python)" as ebapi <<aws>>
  database "RDS MySQL 8 - db.t3.micro" as rds <<aws>>
  storage "S3 - media / assets" as s3 <<aws>>
}

node "Azure - DR / Secundario" {
  component "Container Apps - Ingress HTTPS gestionado" as caapp <<azure>>
  component "Container Apps - FastAPI" as caapi <<azure>>
  database "Azure Database for MySQL - Flexible B1ms" as azdb <<azure>>
  storage "Blob Storage" as blob <<azure>>
}

user --> r53 : HTTPS
r53 --> alb : primario (healthy)
r53 ..> caapp : secundario (failover)
alb --> ebapp
alb --> ebapi : /api/*
ebapp --> rds
ebapp --> s3
ebapi --> s3
ebapp --> pusher : eventos realtime
caapp --> azdb
caapp --> blob
caapi --> blob
@enduml
```

**Flujo resumido:** el usuario resuelve el dominio → Route 53 decide AWS o Azure según health check → el balanceador/ingress enruta al contenedor Laravel o a la API según la ruta → la app persiste en su MySQL regional y guarda archivos generados en el object storage de su nube.

---

## 3. Modelo C4

### 3.1 Diagrama de contexto

```plantuml
@startuml KeyNography - Diagrama de Contexto (C4 Nivel 1)
!pragma layout smetana
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 13
skinparam roundcorner 14
skinparam nodesep 55
skinparam ranksep 70
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam actor {
  BackgroundColor #238636
  BorderColor #2EA043
  FontColor #FFFFFF
}
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam database {
  BackgroundColor #3D2E0B
  BorderColor #D29922
  FontColor #E3B341
}
skinparam cloud {
  BackgroundColor #6E40C9
  BorderColor #A371F7
  FontColor #D2A8FF
}
skinparam component<<ext>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}

actor "Estudiante" as student
actor "Docente" as teacher

node "KeyNography - Sistema\nPlataforma educativa de esteganografia multicloud" {
  component "Frontend - Laravel 12 + Vite" as frontend
  component "Backend - API FastAPI" as api
  database "Datos - MySQL 8" as db
}

cloud "Pusher\nCanales realtime (WebSockets)" as pusher
component "Route 53\nDNS con failover AWS/Azure" as dns <<ext>>
cloud "GitHub\nRepositorio + GitHub Actions (CI/CD)" as github

student --> keynography : Usa la plataforma (HTTPS)
teacher --> keynography : Gestiona contenido (HTTPS)
keynography --> pusher : Emite eventos en tiempo real (WSS)
dns --> keynography : Resuelve endpoint activo
github --> keynography : Despliega nuevas versiones
@enduml
```

### 3.2 Diagrama de contenedores

```plantuml
@startuml KeyNography - Diagrama de Contenedores (C4 Nivel 2 MultiCloud)
!pragma layout smetana
left to right direction
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam roundcorner 14
skinparam nodesep 40
skinparam ranksep 60
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam actor {
  BackgroundColor #238636
  BorderColor #2EA043
  FontColor #FFFFFF
}
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam component<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam component<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam database<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam database<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam storage<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam storage<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam component<<ext>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}
skinparam cloud {
  BackgroundColor #6E40C9
  BorderColor #A371F7
  FontColor #D2A8FF
}

actor "Usuario (navegador)" as user

node "AWS - Cluster primario" {
  component "ALB - TLS + balanceo + health checks" as alb <<aws>>
  component "App Laravel - PHP 8.3 + Nginx (EB/Docker)" as eb_app <<aws>>
  component "API FastAPI - Python + Uvicorn (EB/Docker)" as eb_api <<aws>>
  database "MySQL 8 - RDS db.t3.micro" as rds <<aws>>
  storage "S3 - Object Storage" as s3 <<aws>>
}

node "Azure - DR / Secundario" {
  component "Ingress - HTTPS gestionado (Container Apps)" as ca_ingress <<azure>>
  component "App Laravel - misma imagen Docker" as ca_app <<azure>>
  component "API FastAPI - misma imagen Docker" as ca_api <<azure>>
  database "MySQL Flexible - B1ms" as azdb <<azure>>
  storage "Blob Storage" as blob <<azure>>
}

cloud "Pusher" as pusher
component "Route 53" as r53 <<ext>>

user --> r53 : DNS lookup
r53 --> alb : Endpoint primario (HTTPS)
r53 ..> ca_ingress : Endpoint secundario (HTTPS)
alb --> eb_app : /*
alb --> eb_api : /api/*
eb_app --> rds : SQL
eb_app --> s3 : Archivos
eb_api --> s3 : Archivos
eb_app --> pusher : Eventos (WSS)
ca_ingress --> ca_app : HTTP
ca_ingress --> ca_api : HTTP
ca_app --> azdb : SQL
ca_app --> blob : Archivos
ca_api --> blob : Archivos
@enduml
```

---

## 4. Despliegue en AWS

### 4.1 Servicios utilizados (créditos académicos AWS Academy / AWS Educate)

| Servicio | Uso | Capa gratuita / créditos |
|---|---|---|
| **Elastic Beanstalk** | Hosting Laravel + FastAPI (plataforma Docker) | Sin costo extra sobre EC2; incluye **ALB, ASG, health checks y auto-scaling** |
| **EC2 `t3.micro`/`t2.micro`** | Instancias detrás del ASG de EB | 750 h/mes gratis primer año; en Academy se cubre con créditos |
| **Application Load Balancer** | Terminación TLS + balanceo | ~$16–22/mes — **cubierto por créditos**; es el componente que habilita HA real |
| **RDS MySQL `db.t3.micro`** | Base de datos | 750 h/mes gratis primer año (Single-AZ); Multi-AZ duplica costo |
| **S3** | Medios generados + assets | Primeros 5 GB gratis permanente |
| **ACM** | Certificado TLS `*.keynography...` | Gratis |
| **Route 53** | Failover + health checks | Hosted zone $0.50/mes + queries mínimas |
| **ECR** | Registro de imágenes Docker | Primeros 500 MB/mes gratis |
| **CloudWatch** | Logs y métricas | Tier básico gratis |
| **SQS** *(opcional)* | Cola para jobs | 1M requests/mes gratis |

> **Nota honesta sobre "gratuito":** los servicios de balanceo (ALB) y RDS no tienen free tier permanente en AWS; dependen de los **créditos de AWS Academy** (típicamente $100–200) o del free tier del primer año. Es la opción correcta para cumplir el requisito de LB + HA.

### 4.2 Topología AWS

```plantuml
@startuml AWS - Topologia de Red y Alta Disponibilidad
left to right direction
!pragma layout smetana
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam roundcorner 14
skinparam nodesep 35
skinparam ranksep 50
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam component<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam database<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam storage<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam component<<ext>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}
skinparam cloud {
  BackgroundColor #6E40C9
  BorderColor #A371F7
  FontColor #D2A8FF
}

component "Internet / Usuarios" as internet <<ext>>
component "Route 53 + Health Checks" as r53 <<ext>>

node "AWS Cloud - Region us-east-1" {
  node "VPC 10.0.0.0/16" {
    node "Public Subnets (2 AZs)" {
      component "ALB + ACM TLS\nTarget Groups: app + api" as alb <<aws>>
    }
    node "Private Subnets - Compute" {
      node "ASG app - min 2 instancias (2 AZs)" as asg_app {
        component "EC2 - EB Laravel container" as ec2app <<aws>>
        component "EC2 - EB Laravel container" as ec2app2 <<aws>>
      }
      node "ASG api - min 2 instancias (2 AZs)" as asg_api {
        component "EC2 - EB FastAPI container" as ec2api <<aws>>
        component "EC2 - EB FastAPI container" as ec2api2 <<aws>>
      }
    }
    node "Private Subnets - Data" {
      database "RDS MySQL 8 - db.t3.micro" as rds <<aws>>
    }
  }
  storage "S3 - keynography-media" as s3 <<aws>>
  component "Secrets Manager" as sm <<aws>>
  component "CloudWatch - Logs + Metricas" as cw <<aws>>
}

cloud "Pusher" as pusher

internet --> r53 : HTTPS
r53 --> alb : healthy -> IP del ALB
alb --> asg_app : /*
alb --> asg_api : /api/*
asg_app --> rds : SQL (puerto 3306)
asg_app --> sm : credenciales DB
asg_app --> s3 : archivos generados
asg_api --> s3 : archivos generados
asg_app --> pusher : eventos realtime (WSS)
asg_app ..> cw : logs y metricas
asg_api ..> cw : logs y metricas
@enduml
```

### 4.3 Decisiones de diseño AWS

- **Elastic Beanstalk con plataforma Docker**: se reutiliza el `Dockerfile` actual sin cambios. EB crea automáticamente el **ALB + ASG + health checks** → alta disponibilidad sin escribir infraestructura como código compleja (opcional: `eb cli`/`aws cloudformation` si se quiere IaC).
- **Ruteo por path**: `/api/*` → target group de FastAPI; el resto → Laravel. Alternativa más limpia a mediano plazo: subdominio `api.dominio.com` con *host-based routing*.
- **ASG mínimo 1, deseable 2**: para HA real se configura `min_size=2` repartidos en 2 AZs (costo doble de instancias, asumible con créditos). Health check del ALB reemplaza instancias unhealthy automáticamente.
- **RDS Single-AZ** para el MVP; el failover multicloud hacia Azure cubre el escenario de desastre. RDS Multi-AZ queda como mejora futura (~$25/mes extra).
- **Secretos** en AWS Secrets Manager (o variables de entorno del EB environment), nunca en el repo.
- **Storage**: el volumen `storage_data` de Laravel se monta en EFS si se escala a >1 instancia; en MVP con 1–2 instancias se usa disco local efímero + S3 para lo persistente. Los `generated_audios`/`generated_images` de la API van directo a S3.
- **Pusher** se mantiene como SaaS común a ambos proveedores (WebSockets gestionados; el free tier de Pusher alcanza para el proyecto).

### 4.4 Variables de entorno AWS (ejemplo)

```env
APP_ENV=production
APP_URL=https://keynography.tudominio.com
DB_CONNECTION=mysql
DB_HOST=<rds-endpoint>
DB_DATABASE=keynography
FILESYSTEM_DISK=s3
AWS_BUCKET=keynography-media
QUEUE_CONNECTION=database
API_URL=https://api.tudominio.com   # o https://dominio.com/api si se usa path routing
PUSHER_APP_ID=...
PUSHER_KEY=...
PUSHER_SECRET=...
PUSHER_CLUSTER=us2
```

---

## 5. Despliegue en Azure

### 5.1 Servicios utilizados (Azure for Students — $100 crédito + servicios gratuitos)

| Servicio | Uso | Capa gratuita / créditos |
|---|---|---|
| **Azure Container Apps** | Hosting Laravel + FastAPI (consumo) | ~180 000 vCPU-s y 2M requests/mes gratis; HTTPS ingress gestionado (equivale a LB+TLS) |
| **Azure Container Registry** | Imágenes Docker | Basic: 10 GB incluidos |
| **Azure Database for MySQL Flexible Server** | MySQL `B1ms` Burstable | **Gratis 12 meses** para estudiantes |
| **Azure Blob Storage** | Medios generados | 5 GB gratis 12 meses |
| **Azure DNS** *(opcional)* | Zona DNS | $0.50/mes por zona |
| **Azure Monitor / Log Analytics** | Logs y métricas | 5 GB/mes gratis |
| **Azure Key Vault** | Secretos | Operaciones gratuitas generosas |

### 5.2 Topología Azure

```plantuml
@startuml Azure - Topologia Container Apps
left to right direction
!pragma layout smetana
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam roundcorner 14
skinparam nodesep 40
skinparam ranksep 50
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam component<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam database<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam storage<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam component<<ext>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}
skinparam cloud {
  BackgroundColor #6E40C9
  BorderColor #A371F7
  FontColor #D2A8FF
}

actor "Usuario" as user
component "Route 53 (health check failover)" as r53 <<ext>>

node "Microsoft Azure - Subscription Students" {
  node "Resource Group: rg-keynography-prod" {
    node "Container Apps Environment (Consumption)" as cae {
      component "app-laravel - replicas 1 a 3" as appca <<azure>>
      component "app-api - replicas 1 a 3" as apica <<azure>>
    }
    component "Azure Container Registry - Basic (10 GB)" as acr <<azure>>
    database "Azure Database for MySQL Flexible - B1ms + PITR" as mysql <<azure>>
    storage "Storage Account - Blob containers" as st <<azure>>
    component "Key Vault - secretos" as kv <<azure>>
    component "Azure Monitor / Log Analytics" as mon <<azure>>
  }
}

cloud "Pusher" as pusher
cloud "GitHub Actions - CI/CD" as gha <<ext>>

user --> r53 : DNS
r53 ..> cae : failover HTTPS
cae --> appca : HTTP
gha ..> acr : docker push (imagen sha)
acr ..> cae : pull imagen
appca --> mysql : SQL privado/VNet
appca --> st : Blob
apica --> st : Blob
appca --> kv : secretos (referenciados)
appca --> pusher : eventos WSS
appca ..> mon : logs y metricas
apica ..> mon : logs y metricas
@enduml
```

### 5.3 Decisiones de diseño Azure

- **Container Apps en Consumption plan**: escala a cero opcional, réplicas mínimas configurables, HTTPS automático con certificado gestionado → cubre "balanceo" sin administrar un Load Balancer explícito (equivalente PaaS al ALB de AWS).
- **Misma imagen Docker** que en AWS → paridad real multi-cloud, un solo `docker build` sirve para ambos.
- **MySQL Flexible Server B1ms**: el tier gratuito de 12 meses encaja con el crédito de Azure for Students.
- **Réplicas `min=1, max=3`**: HA limitada (una sola región); el failover real lo da Route 53 hacia/desde AWS. Para HA intra-Azure se puede subir a `min=2` (costo bajo en consumption).
- **Sin replicación automática de datos** entre RDS y MySQL en el MVP (complejidad alta). Estrategia: **cold standby** — la BD de Azure se mantiene migrada periódicamente (dump/restauración) y se promueve manualmente en failover. Ver sección 10.

---

## 6. DNS, balanceo y alta disponibilidad

### 6.1 Estrategia DNS con Route 53

```plantuml
@startuml DNS - Failover Routing Route53
left to right direction
!pragma layout smetana
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam roundcorner 14
skinparam nodesep 40
skinparam ranksep 50
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam actor {
  BackgroundColor #238636
  BorderColor #2EA043
  FontColor #FFFFFF
}
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam component<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam component<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam component<<ok>> {
  BackgroundColor #0F5323
  BorderColor #238636
  FontColor #56D364
}
skinparam component<<standby>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}
skinparam component<<ext>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}

actor "Usuario" as u
component "Route 53 - Hosted Zone" as r53 <<ext>>
component "Health Check - HTTPS /up cada 30s" as hc <<ext>>

node "Record Set: keynography.com - FAILOVER" {
  component "PRIMARY - AWS ALB (aws-primary)" as pri <<aws>>
  component "SECONDARY - Azure CA (azure-dr)" as sec <<standby>>
}
node "Record Set: api.keynography.com - FAILOVER" {
  component "PRIMARY - AWS ALB /api" as apri <<aws>>
  component "SECONDARY - Azure api" as asec <<standby>>
}

u --> r53 : consulta DNS
r53 --> hc : evalua estado
hc --> pri : healthy -> devuelve IP AWS
hc --> sec : primary fallo -> devuelve IP Azure
hc --> apri : healthy -> devuelve IP AWS
hc --> asec : primary fallo -> devuelve IP Azure
@enduml
```

- **Failover routing** (no simple latency): Route 53 devuelve AWS si el health check pasa; si falla de forma sostenida, devuelve Azure automáticamente (DNS TTL ~60s → propagación rápida).
- Health check contra un endpoint barato: `GET /up` de Laravel o `GET /health` de FastAPI.
- Coste: health checks adicionales ~$0.50/mes c/u — trivial con créditos.
- **Limitación conocida**: el failover es de *aplicación*, no de datos. Ver sección 10.

### 6.1.1 Alternativa sin Route 53

Si se quiere cero costo: un único dominio con dos subdominios (`aws.dominio.com`, `azure.dominio.com`) y conmutación manual documentada. Route 53 failover es la versión "pro" y recomendable para el proyecto.

### 6.2 Alta disponibilidad por nube

| Capa | AWS | Azure |
|---|---|---|
| Compute | ASG min 2, 2 AZs, health checks ALB | Container Apps min 1–2 réplicas, réplicas en distintos nodos |
| Datos | RDS Single-AZ (+Multi-AZ opcional) | MySQL Flexible con alta disponibilidad opcional (costo extra) |
| TLS | ACM automático en ALB | Certificado gestionado en ingress |
| Recuperación | EB rollback + snapshots RDS | Revisión de revisiones de Container Apps + backups MySQL |

---

## 7. CI/CD completo

### 7.1 Estrategia de ramas y entornos

```plantuml
@startuml Estrategia de Ramas y Entornos
!pragma layout smetana
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam roundcorner 14
skinparam nodesep 45
skinparam ranksep 60
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam component<<branch>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #C9D1D9
}
skinparam component<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam component<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam component<<local>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}

node "Git - Ramas" {
  component "feature/*" as f <<branch>>
  component "develop" as d <<branch>>
  component "main" as m <<branch>>
  component "hotfix/*" as h <<branch>>
}
node "Entornos de despliegue" {
  component "Dev - local / EB env dev" as ed <<local>>
  component "Staging - Azure Container Apps" as es <<azure>>
  component "Prod AWS - primario" as ep <<aws>>
  component "Prod Azure - DR pasivo" as edr <<azure>>
}

f --> d : PR
d --> m : release
h --> m : PR urgente
m --> ep : deploy automatico
d --> es : deploy automatico
m --> edr : deploy programado/manual
@enduml
```

| Entorno | Despliegue | Propósito |
|---|---|---|
| Local | `php artisan serve` + `uvicorn` + docker-compose | Desarrollo |
| Staging | Azure Container Apps (`--environment staging`) | QA/pre-producción, además actúa como DR tibio |
| Producción AWS | EB environments `keynography-prod` + `keynography-api` | Primario, failover origen |
| Producción Azure | Container Apps prod | DR pasivo; mismo artefacto inmutable |

**Principio:** un artefacto Docker inmutable por commit → el mismo `sha256` corre en staging y en ambas nubes. Nada de "en mi máquina funcionaba".

### 7.2 Pipeline de la aplicación (Laravel)

```yaml
# .github/workflows/deploy-app.yml  (extracto)
name: Deploy App
on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

env:
  AWS_REGION: us-east-1
  EB_APP: keynography
  EB_ENV: keynography-prod

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', extensions: sqlite }
      - run: composer install --no-interaction --prefer-dist
      - run: php artisan test            # PHPUnit
      - run: npm ci && npm run build     # Vite assets

  docker:
    needs: test
    runs-on: ubuntu-latest
    outputs: { tag: ${{ steps.meta.outputs.tags }} }
    steps:
      - uses: docker/setup-buildx-action@v3
      - uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: ${{ secrets.AWS_DEPLOY_ROLE }}   # OIDC, sin access keys
          aws-region: ${{ env.AWS_REGION }}
      - uses: aws-actions/amazon-ecr-login@v2
      - uses: docker/build-push-action@v5
        with:
          context: .
          push: true
          tags: ${{ secrets.ECR_REGISTRY }}/keynography-app:${{ github.sha }}

  deploy-aws:
    needs: docker
    runs-on: ubuntu-latest
    environment: production-aws
    steps:
      - uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: ${{ secrets.AWS_DEPLOY_ROLE }}
          aws-region: ${{ env.AWS_REGION }}
      - name: Deploy Elastic Beanstalk
        run: |
          pip install awsebcli
          eb init keynography --platform Docker --region $AWS_REGION
          eb use $EB_ENV
          eb deploy --staged --timeout 20

  deploy-azure:
    needs: docker
    runs-on: ubuntu-latest
    environment: production-azure
    steps:
      - uses: azure/login@v2
        with:
          creds: ${{ secrets.AZURE_CREDENTIALS }}          # Service Principal (OIDC recomendado)
      - run: |
          az acr login --name ${{ secrets.ACR_NAME }}
          docker pull ${{ secrets.ECR_REGISTRY }}/keynography-app:${{ github.sha }}
          docker tag  ${{ secrets.ECR_REGISTRY }}/keynography-app:${{ github.sha }} \
                       ${{ secrets.ACR_NAME }}.azurecr.io/keynography-app:${{ github.sha }}
          docker push ${{ secrets.ACR_NAME }}.azurecr.io/keynography-app:${{ github.sha }}
          az containerapp update \
            --name keynography-app \
            --resource-group rg-keynography-prod \
            --image ${{ secrets.ACR_NAME }}.azurecr.io/keynography-app:${{ github.sha }}
```

> **Detalle práctico:** como ECR y ACR son registros distintos, el paso `deploy-azure` hace *retag* de la imagen. Alternativa más limpia: construir una vez, hacer push a **ambos** registros en el job `docker` (multi-registry push con `docker buildx --push` y varios `tags`).

### 7.3 Pipeline de la API (FastAPI)

Mismo patrón, con `pytest` + lint (`ruff`) en lugar de PHPUnit, imagen desde `keyNographi API/Dockerfile`, y despliegue a los environments `keynography-api` (EB) y `keynography-api` (Container App). Puerto interno 7000/8000 según Dockerfile; el ingress/ALB lo expone como `/api/*` o `api.dominio.com`.

### 7.4 Flujo de despliegue y rollback

```plantuml
@startuml CI-CD - Flujo de Despliegue y Rollback
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam ActivityBackgroundColor #21262D
skinparam ActivityBorderColor #58A6FF
skinparam ActivityFontColor #C9D1D9
skinparam ActivityDiamondBackgroundColor #1F2937
skinparam ActivityDiamondBorderColor #F85149
skinparam ActivityDiamondFontColor #FF9B95
skinparam PartitionBackgroundColor #161B22
skinparam PartitionBorderColor #30363D
skinparam PartitionFontColor #8B949E
skinparam ConditionEndBackgroundColor #238636

start
partition "GitHub Actions - Pipeline" {
  :Push / merge a main;
  :Checkout + tests (PHPUnit / pytest);
  if (¿Tests fallan?) then (sí)
    :Notificar fallo y abortar pipeline;
    stop
  endif
  :Build assets (Vite) + docker build;
  :Push imagen inmutable (sha256) a ECR y ACR;
}
partition "Deploy AWS - Produccion primaria" {
  :eb deploy -> Elastic Beanstalk;
  :Health check ALB;
  if (¿Healthy?) then (sí)
    :Release activo en AWS;
  else (no)
    :eb deploy rollback automatico;
  endif
}
partition "Deploy Azure - DR pasivo" {
  :az containerapp update -> nueva revision;
  :Revision al 0% + smoke test;
  if (¿Smoke OK?) then (sí)
    :Promover revision al 100%;
  else (no)
    :Revision queda inactiva (rollback trivial);
  endif
}
partition "Cierre" {
  :Notificación (Slack / email) con estado de ambos despliegues;
}
stop
@enduml
```

- **AWS/EB**: rollback = `eb deploy` de la versión anterior o "Rollback" en la consola (versionado de releases nativo).
- **Azure/Container Apps**: las revisiones inmutables permiten *traffic splitting* — se despliega la nueva revisión al 0%, se hace smoke test y se promueve al 100%. Rollback = re-etiquetar la revisión anterior.
- **Smoke tests post-deploy** en ambos: `curl /up`, login de prueba, un ciclo esteganografía→descarga.

### 7.5 Diagrama completo del pipeline

```plantuml
@startuml CI-CD - Pipeline Multicloud Completo
left to right direction
!pragma layout smetana
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam roundcorner 14
skinparam nodesep 40
skinparam ranksep 55
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam component<<gh>> {
  BackgroundColor #24292E
  BorderColor #58A6FF
  FontColor #58A6FF
}
skinparam component<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam component<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam database<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam storage<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}

node "GitHub" {
  component "Repo KeyNography" as repo <<gh>>
  component "GitHub Actions - workflows" as gha <<gh>>
  component "Environments + Secrets (production-aws/azure)" as envs <<gh>>
}

node "AWS" {
  component "ECR - registro de imagenes" as ecr <<aws>>
  component "Elastic Beanstalk - app + api" as eb <<aws>>
  database "RDS MySQL" as data_aws <<aws>>
  storage "S3 - media" as s3_aws <<aws>>
}

node "Azure" {
  component "ACR - registro de imagenes" as acr <<azure>>
  component "Container Apps - staging + prod" as ca <<azure>>
  database "MySQL Flexible" as data_az <<azure>>
  storage "Blob Storage" as blob_az <<azure>>
}

repo --> gha : push main
gha --> envs : aprueba entorno (OIDC)
gha --> ecr : docker push sha256
gha --> acr : docker push sha256
ecr --> eb : eb deploy
acr --> ca : az containerapp update
eb --> data_aws : runtime
eb --> s3_aws : runtime
ca --> data_az : runtime
ca --> blob_az : runtime
@enduml
```

---

## 8. Flujos de datos y secuencias

### 8.1 Flujo principal: esteganografía de imagen

```plantuml
@startuml Secuencia - Ocultar mensaje en imagen
autonumber
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam sequenceArrowThickness 2
skinparam participant {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam participant<<laravel>> {
  BackgroundColor #0B2540
  BorderColor #58A6FF
  FontColor #79B8FF
}
skinparam participant<<fastapi>> {
  BackgroundColor #0F2E1D
  BorderColor #3FB950
  FontColor #56D364
}
skinparam participant<<pusher>> {
  BackgroundColor #2D1B4E
  BorderColor #A371F7
  FontColor #D2A8FF
}
skinparam database {
  BackgroundColor #3D2E0B
  BorderColor #D29922
  FontColor #E3B341
}
skinparam actor {
  BackgroundColor #238636
  BorderColor #2EA043
  FontColor #FFFFFF
}

actor "Usuario" as u
participant "Laravel (Blade/Vite)" as app <<laravel>>
participant "FastAPI /steganography" as api <<fastapi>>
participant "MySQL" as db
database "S3 / Blob" as s3
participant "Pusher" as pusher <<pusher>>

u -> app : Sube imagen + mensaje
activate app
app -> db : Registra trabajo en cola
app -> pusher : job:created (progreso 0%)
app -> api : POST /hide (multipart)
activate api
api -> api : LSB embedding (Pillow)
api -> s3 : Guarda imagen resultante
api --> app : { result_url, capacity }
deactivate api
app -> db : Guarda metadatos + resultado
app -> pusher : job:completed
deactivate app
pusher --> u : Evento realtime -> descarga resultado
note right of pusher
  El usuario ve el progreso y
descarga el resultado sin polling
end note
@enduml
```

### 8.2 Flujo de lectura de archivos generados

Las imágenes/audio generados viven en object storage. Recomendación: la API devuelve **URLs prefirmadas** (S3) o **SAS tokens** (Blob) con expiración corta en lugar de proxy por Laravel — menos carga y más seguro. En MVP con archivos públicos puede servirse directo desde el bucket/container con URL pública controlada.

---

## 9. Observabilidad y operaciones

| Capacidad | AWS | Azure |
|---|---|---|
| Logs | CloudWatch Logs (EB los envía automático) | Log Analytics (Container Apps envía automático) |
| Métricas | CloudWatch (CPU, ALB 5xx, RDS) | Azure Monitor / Container Apps metrics |
| Errores app | `php artisan` logs → CloudWatch; Sentry *(opcional, free tier)* | Igual → Log Analytics |
| Uptime | Route 53 health checks cada 30 s | Mismos health checks |
| Alertas | CloudWatch Alarms → SNS email (gratis) | Azure Monitor alert rules → email |

Recomendación ligera: **Sentry free tier** (5k errores/mes) en ambas nubes para excepciones de Laravel y FastAPI — un solo panel para el sistema multicloud.

---

## 10. Continuidad de negocio y failover

```plantuml
@startuml DR - Continuidad y Failover
left to right direction
!pragma layout smetana
skinparam backgroundColor #0B0E14
skinparam shadowing false
skinparam defaultFontName "Segoe UI"
skinparam defaultFontSize 12
skinparam roundcorner 14
skinparam nodesep 40
skinparam ranksep 60
skinparam ArrowColor #8B949E
skinparam ArrowFontColor #C9D1D9
skinparam ArrowFontSize 11
skinparam node {
  BackgroundColor #161B22
  BorderColor #30363D
}
skinparam component {
  BackgroundColor #21262D
  BorderColor #58A6FF
  FontColor #C9D1D9
}
skinparam component<<aws>> {
  BackgroundColor #232F3E
  BorderColor #FF9900
  FontColor #FF9900
}
skinparam component<<azure>> {
  BackgroundColor #0B2540
  BorderColor #0078D4
  FontColor #7CC4FF
}
skinparam component<<ok>> {
  BackgroundColor #0F5323
  BorderColor #238636
  FontColor #56D364
}
skinparam component<<alert>> {
  BackgroundColor #3D1214
  BorderColor #F85149
  FontColor #FF9B95
}
skinparam component<<ext>> {
  BackgroundColor #21262D
  BorderColor #6E7681
  FontColor #8B949E
}

node "ESTADO NORMAL" {
  component "Route53 -> AWS PRIMARY sirviendo" as n1 <<ok>>
  component "Azure en STANDBY - dump diario aplicado" as n2 <<azure>>
}
node "ESTADO FAILOVER" {
  component "Health check AWS falla sostenido" as f1 <<alert>>
  component "Route53 -> AZURE sirve trafico" as f2 <<azure>>
  component "Datos: ultima sync - RPO ~24h" as f3 <<alert>>
}
node "ESTADO RECOVERY" {
  component "AWS restaurado - health OK" as r1 <<aws>>
  component "Failback manual programado" as r2 <<ext>>
  component "Resync datos Azure -> AWS" as r3 <<ext>>
}

n1 -[hidden]-> n2
n2 -[hidden]-> f1
f1 -[hidden]-> f2
f2 -[hidden]-> r1

n1 --> f1 : health check caido >2min
f2 --> r1 : AWS vuelve healthy
r3 --> n1 : failback completo
@enduml
```

| Métrica | Objetivo MVP | Notas |
|---|---|---|
| **RTO** (tiempo de recuperación) | ~5–15 min | Automático vía DNS; depende del TTL (60s) |
| **RPO** (pérdida de datos) | 24 h (dump diario) | Aceptable para contenido educativo; mejora futura: binlog replication gestionada |
| **Failback** | Manual, ventana programada | Re-sincronizar BD antes de volver el tráfico |

**Procedimiento de failover (runbook):**
1. Confirmar health check caído en Route 53 (o forzar con "Failover" manual en consola).
2. Verificar que Azure responde y que la BD tiene el último dump.
3. DNS conmuta solo; comunicar a usuarios.
4. Post-incidente: restaurar AWS, resincronizar datos, documentar.

---

## 11. Costos estimados (créditos académicos)

| Concepto | AWS/mes | Azure/mes | Notas |
|---|---|---|---|
| Compute app+api | ~$16–25 (2× t3.micro + ALB) | ~$0–10 (consumption) | ALB es el costo fijo principal en AWS |
| Base de datos | ~$15 (RDS t3.micro) | $0 (12 meses gratis B1ms) | |
| Storage | <$1 (S3 5GB gratis) | $0–2 (Blob) | |
| DNS/health checks | ~$1.50 | — | Route 53 |
| **Total estimado** | **~$32–42** | **~$0–12** | Ambos cubiertos por créditos académicos |

Presupuesto sugerido de créditos: **AWS ~$50/mes**, **Azure ~$20/mes** → ~$840/año, dentro de los $100–200 de AWS Academy + $100 Azure for Students si se gestiona bien (Azure sobra; AWS es el que más consume por ALB+RDS).

---

## 12. Roadmap de implementación

| Fase | Entregable | Dependencias |
|---|---|---|
| **0. Prep** | Dockerfiles validados, `.dockerignore`, health endpoints (`/up`, `/health`) | — |
| **1. AWS base** | EB env dev → prod, RDS, S3, ACM, secrets | Cuenta AWS Academy activa |
| **2. CI/CD AWS** | Workflow test→ECR→EB con OIDC | Fase 1 |
| **3. Azure base** | ACR, MySQL Flexible, Container Apps staging | Azure for Students activo |
| **4. CI/CD Azure** | Workflow multi-registry + containerapp update | Fases 2–3 |
| **5. DNS + failover** | Route 53 hosted zone, failover records, health checks | Dominios apuntando |
| **6. DR + runbook** | Dump/restauración programada, simulacro de failover | Fase 5 |
| **7. Endurecimiento** | Réplicas min=2, backups automatizados, alertas, Sentry | Post-MVP |

---

## Anexo A — Checklist de variables de entorno por entorno

- `APP_ENV`, `APP_URL`, `APP_KEY` (por entorno, nunca compartida)
- `DB_*` (endpoint regional de cada nube)
- `FILESYSTEM_DISK` / `AWS_BUCKET` / `AZURE_STORAGE_*`
- `QUEUE_CONNECTION`, `CACHE_STORE` (database en MVP)
- `PUSHER_*` (común)
- `API_URL` (URL pública de la API según proveedor activo)

## Anexo B — Decisiones descartadas (y por qué)

| Alternativa | Por qué no |
|---|---|
| Kubernetes (EKS/AKS) | Sobre-ingeniería para este proyecto; EB+Container Apps dan el mismo resultado con 10% del esfuerzo |
| Replicación activa de BD entre nubes | Complejidad y costo desproporcionados; cold standby + failover DNS cubre el requisito académico |
| Serverless puro (Lambda/Lambda Azure Functions) | Laravel monolítico + sesiones/cola no encaja sin refactor grande |
| Cloudflare como único DNS | Válido, pero Route 53 health checks + failover se integra mejor con AWS y es parte del temario |
