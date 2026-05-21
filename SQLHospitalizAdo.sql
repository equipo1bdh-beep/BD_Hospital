-- CREACION DE BASE DE DATOS
-- POR: LMN

CREATE DATABASE HospitalizAdo
USE HospitalizAdo
-- DROP DATABASE HospitalizAdo

CREATE TABLE Citas(
id_cita INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_paciente INT NOT NULL, --// futura FK LISTA
id_doctor INT NOT NULL, --// futura FK LISTA
id_estatusC INT NOT NULL, --// futura FK LISTA
fecha_asignacion DATE NOT NULL,
fecha_cita DATE NOT NULL,
hora_asignacion TIME NOT NULL,
hora_cita TIME NOT NULL
)

		--// AÑADIMOS LAS FK DE ESTATUSC, DOCTOR Y PACIENTE A CITAS
		ALTER TABLE Citas
		ADD CONSTRAINT FK_citas_paciente
		FOREIGN KEY (id_paciente)
		REFERENCES Paciente (id_paciente)

		ALTER TABLE Citas
		ADD CONSTRAINT FK_citas_doctor
		FOREIGN KEY (id_doctor)
		REFERENCES Doctor (id_doctor)

		ALTER TABLE Citas
		ADD CONSTRAINT FK_citas_EstatusCita
		FOREIGN KEY (id_estatusC)
		REFERENCES Estatus_Cita (id_estatusC)
		--//

CREATE TABLE Paciente(
id_paciente INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
nombres NVARCHAR(50) NOT NULL,
ap_paterno NVARCHAR(20) NOT NULL,
ap_materno NVARCHAR(20) NOT NULL,
tipo_sangre NVARCHAR(10) NOT NULL,
fecha_nacimiento DATE NOT NULL,
curp NVARCHAR(18) NOT NULL,
genero NVARCHAR(20) NOT NULL,
contraseña NVARCHAR(20) NOT NULL
)

CREATE TABLE Doctor(
id_doctor INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_empleado INT NOT NULL, --// futura FK
id_especialidad INT NOT NULL, --// futura FK
cedula NVARCHAR(50) NOT NULL
)

		--// AÑADIRMOS LAS FK DE ID EMPLEADO Y ESPECIALIDAD A DOCTOR
		ALTER TABLE Doctor
		ADD CONSTRAINT FK_doctor_empleado
		FOREIGN KEY (id_empleado)
		REFERENCES Empleado (id_empleado)

		ALTER TABLE Doctor
		ADD CONSTRAINT FK_doctor_especialidad
		FOREIGN KEY (id_especialidad)
		REFERENCES Especialidades (id_especialidad)
		--//


CREATE TABLE Empleado(
id_empleado INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_tipo_empleado INT NOT NULL, -- futura FK
id_ventanilla INT NOT NULL, -- futura FK
nombres_emp NVARCHAR(50) NOT NULL,
ap_paterno_emp NVARCHAR(50) NOT NULL,
ap_materno_emp NVARCHAR(50) NOT NULL,
rfc NVARCHAR(10) NOT NULL,
estatus_emp NVARCHAR(10) NOT NULL,
curp_emp NVARCHAR (18) NOT NULL,
genero_emp NVARCHAR(10) NOT NULL,
contraseña NVARCHAR(20) NOT NULL
)

		-- AÑADIMOS LAS FK DE TIPO EMPLEADO Y VENTANILLA A EMPLEADO
		ALTER TABLE Empleado
		ADD CONSTRAINT FK_empleado_tipoEmp
		FOREIGN KEY (id_tipo_empleado)
		REFERENCES Tipo_Empleado (id_tipo_empleado)

		ALTER TABLE Empleado
		ADD CONSTRAINT FK_empleado_ventanilla
		FOREIGN KEY (id_ventanilla)
		REFERENCES Ventanilla (id_ventanilla)
		--

CREATE TABLE Tratamientos(
id_tratamiento INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
folio INT NOT NULL, -- futura FK
medicamento NVARCHAR(50) NOT NULL,
tiempo_tratamiento NVARCHAR(50) NOT NULL,
dosis NVARCHAR(50) NOT NULL,
horas_dosis NVARCHAR(20) NOT NULL,
sugerencias NVARCHAR(MAX) NOT NULL
)

		-- AÑADIRMOS LAS FK DE RECETA A TRATAMIENTOS
		ALTER TABLE Tratamientos
		ADD CONSTRAINT FK_tratamientos_receta
		FOREIGN KEY (folio)
		REFERENCES Receta (folio)
		--

CREATE TABLE Historial_Medico(
id_historial INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_padecimiento INT NOT NULL, -- futura FK
id_paciente INT NOT NULL, -- futura FK
estatura NVARCHAR(5) NOT NULL,
peso NVARCHAR(5) NOT NULL,
fecha_registro DATE NOT NULL
)

		-- AÑADIMOS LAS FK DE PACIENTE Y ENFERMEDAD A HISTORIAL
		ALTER TABLE Historial_Medico
		ADD CONSTRAINT FK_historialMed_paciente
		FOREIGN KEY (id_paciente)
		REFERENCES Paciente (id_paciente)

		ALTER TABLE Historial_Medico
		ADD CONSTRAINT FK_historialMed_enfermedades
		FOREIGN KEY (id_padecimiento)
		REFERENCES Enfermedades (id_padecimiento)
		--

CREATE TABLE Ticket_Cita(
id_pago INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_cita INT NOT NULL, -- futura FK
id_estatusTKC INT NOT NULL, -- futura FK
fecha_pago DATE NOT NULL,
fecha_limite DATE NOT NULL,
monto DECIMAL(19,4) NOT NULL,
monto_pagado DECIMAL(19,4) NOT NULL,
cambio DECIMAL(19,4) NOT NULL
)

		-- AÑADIR LAS FK DE CITA Y ESTATUS TICKET CITA A TICKET CITA XD
		ALTER TABLE Ticket_Cita
		ADD CONSTRAINT FK_ticketCi_cita
		FOREIGN KEY (id_cita)
		REFERENCES Citas (id_cita)

		ALTER TABLE Ticket_Cita
		ADD CONSTRAINT FK_ticketCi_EstatusTiC
		FOREIGN KEY (id_estatusTKC)
		REFERENCES Estatus_Ticket_Cita (id_estatusTKC)
		--

CREATE TABLE Ticket (
no_ticket INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_ventanilla INT NOT NULL, -- futura FK
cliente NVARCHAR(50) NOT NULL,
fecha DATE NOT NULL
-- ? total INT NOT NULL
)

		-- AÑADIRMOS LA FK DE VENTANILLA A TICKET
		ALTER TABLE Ticket
		ADD CONSTRAINT FK_ticket_ventanilla
		FOREIGN KEY (id_ventanilla)
		REFERENCES Ventanilla (id_ventanilla)
		--

CREATE TABLE Ticket_Servicios(
id_ticket_serv INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_servicio INT NOT NULL, -- futura FK
no_ticket INT NOT NULL, -- futura FK
cantidad INT NOT NULL, 
subtotal INT NOT NULL
)

		-- AÑADIR LAS FK DE SERVICIO Y TICKET A TICKET SERVICIOS
		ALTER TABLE Ticket_Servicios
		ADD CONSTRAINT FK_ticketSe_servicio
		FOREIGN KEY (id_servicio)
		REFERENCES Servicio (id_servicio)

		ALTER TABLE Ticket_Servicios
		ADD CONSTRAINT FK_ticketSe_ticket
		FOREIGN KEY (no_ticket)
		REFERENCES Ticket (no_ticket)
		--

CREATE TABLE Ticket_Medicamento(
id_ticket_medic INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_medicamento INT NOT NULL, -- futura FK
no_ticket INT NOT NULL, -- futura FK
cantidad INT NOT NULL,
subtotal INT NOT NULL
)

		-- AÑADIMOS LAS FK DE TICKET Y MEDICAMENTO A TICKETMED
		ALTER TABLE Ticket_Medicamento
		ADD CONSTRAINT FK_ticketMe_ticket
		FOREIGN KEY (no_ticket)
		REFERENCES Ticket (no_ticket)

		ALTER TABLE Ticket_Medicamento
		ADD CONSTRAINT FK_ticketMe_medicamento
		FOREIGN KEY (id_medicamento)
		REFERENCES Medicamentos (id_medicamento)
		--

CREATE TABLE Horario(
id_horario INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_oficina INT NOT NULL, -- futura FK
hora_ent TIME NOT NULL,
hora_sal TIME NOT NULL,
dia_semana DATE NOT NULL
)

		-- AÑADIR LAS FK DE OFICINA A HORARIO
		ALTER TABLE Horario
		ADD CONSTRAINT FK_horario_oficina
		FOREIGN KEY (id_oficina)
		REFERENCES Oficina (id_oficina)
		--

CREATE TABLE Oficina(
id_oficina INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_tipo_of INT NOT NULL, --futura FK
piso NVARCHAR(3) NOT NULL,
num_sala NVARCHAR(3) NOT NULL,
edificio NVARCHAR(3) NOT NULL
)

		-- AÑADIR LA FK DE TIPO OFICINA A OFICINA
		ALTER TABLE Oficina
		ADD CONSTRAINT FK_oficina_tipoOf
		FOREIGN KEY (id_tipo_of)
		REFERENCES Tipo_Oficina (id_tipo_of)
		--

CREATE TABLE Receta(
folio INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_cita INT NOT NULL, --futura FK
diagnostico NVARCHAR(MAX) NOT NULL,
observaciones NVARCHAR(MAX) NOT NULL,
fecha_receta DATE NOT NULL
)

		-- AÑADIR LA FK DE CITA A RECETA (??
		ALTER TABLE Receta
		ADD CONSTRAINT FK_receta_cita
		FOREIGN KEY (id_cita)
		REFERENCES Citas (id_cita)
		--

CREATE TABLE Bitacora_Estatus_Cita(
id_bitacora INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_cita INT NOT NULL, --futura FK
estatus_mov NVARCHAR(50) NOT NULL,
fecha_mov DATE NOT NULL,
costo DECIMAL(19,4) NOT NULL,
politica_cancel NVARCHAR(50) NULL,
monto_devuelto DECIMAL(19,4) NULL
)

		-- AÑADIR LA FK DE CITA A BEC
		ALTER TABLE Bitacora_Estatus_Cita
		ADD CONSTRAINT FK_bitacoraEsC_cita
		FOREIGN KEY (id_cita)
		REFERENCES Citas (id_cita)
		--

CREATE TABLE Bitacora_Historial_Medico(
id_log INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_cita INT NOT NULL, --futura FK
id_paciente INT NOT NULL, --futura FK
usuario NVARCHAR(50) NOT NULL,
fecha_cita DATE NOT NULL,
hora_cita TIME NOT NULL,
estatus_consulta NVARCHAR(20) NOT NULL
)

		--AÑADIR LAS FK DE CITA Y PACIENTE A HISTORIALMEDICOBIT
		ALTER TABLE Bitacora_Historial_Medico
		ADD CONSTRAINT FK_bitacoraHiM_cita
		FOREIGN KEY (id_cita)
		REFERENCES Citas (id_cita)

		ALTER TABLE Bitacora_Historial_Medico
		ADD CONSTRAINT FK_bitacoraHiM_paciente
		FOREIGN KEY (id_paciente)
		REFERENCES Paciente (id_paciente)
		--

CREATE TABLE Estatus_Cita(
id_estatusC INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
descripcion NVARCHAR(50) NOT NULL
)

CREATE TABLE Ventanilla(
id_ventanilla INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
no_ticket INT NOT NULL, --futura FK
num_ventanilla NVARCHAR(5) NOT NULL
)

		-- CREAR LLAVE FORANEA DE TICKET A VENTANILLA
		ALTER TABLE Ventanilla
		ADD CONSTRAINT FK_ventanilla_ticket
		FOREIGN KEY (no_ticket)
		REFERENCES Ticket (no_ticket)
		--

CREATE TABLE Alergias_Paciente(
id_aler_pac INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_alergia INT NOT NULL, --futura fk
id_paciente INT NOT NULL -- futura FK
)

		-- bueno, me aburri ya de decir de donde va una a otra xd
		ALTER TABLE Alergias_Paciente
		ADD CONSTRAINT FK_alergiaPa_alergias
		FOREIGN KEY (id_alergia)
		REFERENCES Alergias (id_alergia)

		ALTER TABLE Alergias_Paciente
		ADD CONSTRAINT FK_alergiaPa_paciente
		FOREIGN KEY (id_paciente)
		REFERENCES Paciente (id_paciente)
		--

CREATE TABLE Alergias(
id_alergia INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
nombre NVARCHAR(50) NOT NULL
)

CREATE TABLE Tel_Paciente(
id_telefono INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_paciente INT NOT NULL, --futura FK
telefono NVARCHAR(10) NOT NULL
)

		-- coso
		ALTER TABLE Tel_Paciente
		ADD CONSTRAINT FK_telPa_paciente
		FOREIGN KEY (id_paciente)
		REFERENCES Paciente (id_paciente)
		--

CREATE TABLE Cor_Paciente(
id_correo INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_paciente INT NOT NULL, --futura FK
correo NVARCHAR(10) NOT NULL
)

		-- coso 2 la pelicula
		ALTER TABLE Cor_Paciente
		ADD CONSTRAINT FK_corPa_paciente
		FOREIGN KEY (id_paciente)
		REFERENCES Paciente (id_paciente)
		--

CREATE TABLE Horario_Empleado(
id_hor_emp INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_empleado INT NOT NULL, --Futura FK
id_horario INT NOT NULL, --futura FK
)

		--
		ALTER TABLE Horario_Empleado
		ADD CONSTRAINT FK_horarioEm_empleado
		FOREIGN KEY (id_empleado)
		REFERENCES Empleado (id_empleado)

		ALTER TABLE Horario_Empleado
		ADD CONSTRAINT FK_horarioEm_horario
		FOREIGN KEY (id_horario)
		REFERENCES Horario (id_horario)
		--

CREATE TABLE Tipo_Oficina(
id_tipo_of INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
descripcion NVARCHAR(50) NOT NULL
)

CREATE TABLE Especialidades(
id_especialidad INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
nom_especialidad NVARCHAR(50) NOT NULL,
precio_consulta MONEY NOT NULL
)

CREATE TABLE Tipo_Empleado(
id_tipo_empleado INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
cargo NVARCHAR(15) NOT NULL
)

CREATE TABLE Telefono_Empleado(
id_telefono_emp INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
id_empleado INT NOT NULL, --futura FK
telefono NVARCHAR(10) NOT NULL
)
		
		--
		ALTER TABLE Telefono_Empleado
		ADD CONSTRAINT FK_telefonoEm_empleado
		FOREIGN KEY (id_empleado)
		REFERENCES Empleado (id_empleado)
		--

CREATE TABLE Correo_Empleado(
id_correo_emp INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
id_empleado INT NOT NULL, -- futura FK
correo NVARCHAR(30) NOT NULL
)

		--
		ALTER TABLE Correo_Empleado
		ADD CONSTRAINT FK_correoEm_empleado
		FOREIGN KEY (id_empleado)
		REFERENCES Empleado (id_empleado)
		--

CREATE TABLE Enfermedades(
id_padecimiento INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
nom_padecimiento NVARCHAR(50) NOT NULL
)

CREATE TABLE Medicamentos(
id_medicamento INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
nom_medic NVARCHAR(100) NOT NULL,
precio_medic DECIMAL(19,4) NOT NULL,
cantidad_almacen INT NOT NULL
)

CREATE TABLE Servicio(
id_servicio INT IDENTITY (1,1) PRIMARY KEY NOT NULL,
nombre NVARCHAR(100) NOT NULL,
subtotal DECIMAL(19,4) NOT NULL
)

CREATE TABLE Estatus_Ticket_Cita(
id_estatusTKC INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
desc_estatus NVARCHAR(10) NOT NULL
)

-- FIN DE CREACION DE BD, ADELANTE VA LA CREACION DE 10 REGISTROS