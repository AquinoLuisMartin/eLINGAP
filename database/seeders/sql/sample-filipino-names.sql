CREATE TEMP TABLE sample_filipino_names ON COMMIT DROP AS
WITH vocabulary AS (
    SELECT
        ARRAY['Jose','Juan','Pedro','Antonio','Francisco','Manuel','Ricardo','Eduardo','Roberto','Ernesto','Ramon','Alfredo','Rodolfo','Danilo','Renato','Arturo','Reynaldo','Fernando','Domingo','Leonardo','Salvador','Vicente','Emilio','Teodoro','Benjamin',
            'Andres','Angel','Armando','Bernardo','Bienvenido','Carlos','Cesar','Conrado','Delfin','Diosdado','Edgardo','Efren','Eliseo','Enrique','Esteban','Eugenio','Feliciano','Felipe','Florencio','Fortunato','Gabriel','Gerardo','Gilberto','Gregorio','Guillermo',
            'Ignacio','Isidro','Jacinto','Jaime','Jesus','Joaquin','Jorge','Julian','Julio','Leandro','Lorenzo','Lucio','Luis','Marcelino','Mario','Miguel','Nestor','Nicolas','Norberto','Oscar','Pablo','Patricio','Rafael','Raul','Rogelio'] AS male,
        ARRAY['Maria','Josefina','Corazon','Rosario','Lourdes','Teresita','Remedios','Carmelita','Gloria','Erlinda','Luzviminda','Natividad','Leonora','Concepcion','Cristina','Virginia','Mercedes','Amelita','Milagros','Estelita','Pacita','Nenita','Adoracion','Florentina','Julita',
            'Adela','Adelina','Alberta','Alicia','Amalia','Anastacia','Andrea','Angela','Angelina','Anita','Antonia','Aurora','Beatriz','Benita','Bernardita','Caridad','Carlota','Catalina','Cecilia','Celestina','Clara','Consuelo','Dolores','Dominga','Elena',
            'Elisa','Emilia','Esperanza','Estrella','Eugenia','Felicidad','Felisa','Francisca','Gabriela','Generosa','Gertrudis','Gregoria','Guadalupe','Herminia','Iluminada','Imelda','Ines','Isabel','Jacinta','Jesusa','Josefa','Juana','Julia','Leocadia','Lucia'] AS female,
        ARRAY['Santos','Reyes','Cruz','Bautista','Garcia','Mendoza','Torres','Ramos','Aquino','Castillo','Villanueva','Fernandez','Rivera','Gonzales','Lopez','Perez','Navarro','Santiago','Del Rosario','De Guzman','Mercado','Tolentino','Soriano','Salazar','Aguilar','Dizon','Pascual','Valdez','Manalo','Domingo','De Leon','Enriquez',
            'Abad','Abalos','Agustin','Alcantara','Alfonso','Alonzo','Alvarez','Angeles','Antonio','Arce','Arellano','Atienza','Austria','Balagtas','Basa','Bernardo','Bonifacio','Borja','Bravo','Buenaventura','Bustos','Caballero','Cabrera','Calderon','Campos','Candelaria','Capistrano','Carpio','Carrillo','Castro','Chavez','Cordero',
            'Corpuz','Cuenca','David','De Jesus','De la Cruz','De Vera','Delos Santos','Diaz','Dimaculangan','Dumlao','Escobar','Espino','Espinosa','Estrella','Evangelista','Fajardo','Flores','Francisco','Galang','Gamboa','Guevarra','Hernandez','Ignacio','Jacinto','Jimenez','Lacson','Lazaro','Lim','Lucas','Luna','Mabini','Macapagal',
            'Magbanua','Marquez','Medina','Miranda'] AS surnames
), identities AS (
    SELECT n, (n - 1) / 2 AS given_pair
    FROM generate_series(1, 10000) n
)
SELECT n,
    CASE WHEN n % 2 = 0
        THEN male[1 + given_pair % 75] || ' ' || male[1 + (given_pair % 75 + 1 + given_pair / 75) % 75]
        ELSE female[1 + given_pair % 75] || ' ' || female[1 + (given_pair % 75 + 1 + given_pair / 75) % 75] END AS first_name,
    surnames[1 + (n + 16) % 100] AS middle_name,
    surnames[1 + (n - 1) % 100] AS last_name
FROM identities CROSS JOIN vocabulary;
ALTER TABLE sample_filipino_names ADD PRIMARY KEY (n);

DO $$
BEGIN
    IF (SELECT count(DISTINCT first_name) FROM sample_filipino_names) <> 10000
        OR (SELECT count(DISTINCT last_name) FROM sample_filipino_names) <> 100
        OR EXISTS (SELECT FROM sample_filipino_names WHERE first_name IS NULL OR middle_name IS NULL
            OR last_name IS NULL OR last_name LIKE '%-%') THEN
        RAISE EXCEPTION 'Sample surname distribution failed';
    END IF;
END $$;
