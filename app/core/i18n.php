<?php
// app/core/i18n.php
// Helpers simples de internacionalização (pt / en).

declare(strict_types=1);

// Definição de idiomas suportados
const APP_LANG_DEFAULT = 'pt';
const APP_LANG_SUPPORTED = ['pt', 'en'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$selectedLang = $_SESSION['lang'] ?? APP_LANG_DEFAULT;
if (isset($_GET['lang']) && in_array($_GET['lang'], APP_LANG_SUPPORTED, true)) {
    $selectedLang = $_GET['lang'];
    $_SESSION['lang'] = $selectedLang;
}

/**
 * Retorna o idioma atual (ex.: 'pt' ou 'en').
 */
function current_lang(): string
{
    return $_SESSION['lang'] ?? APP_LANG_DEFAULT;
}

/**
 * Tabela de traduções básica.
 * Use chaves simples como 'nav.dashboard', 'action.login' etc.
 */
$APP_TRANSLATIONS = [
    'pt' => [
        'nav.projeto'       => 'Projeto',
        'nav.planejamento'  => 'Planejamento',
        'nav.fluxograma'    => 'Fluxograma',
        'nav.esquemas'      => 'Esquemas eletrônicos',
        'nav.dispositivos'  => 'Dispositivos',
        'nav.dashboard'     => 'Dashboard',
        'action.login'      => 'Entrar',
        'action.register'   => 'Registrar',
        'action.logout'     => 'Sair',
        'theme.light'       => 'Claro',
        'theme.dark'        => 'Escuro',
        'lang.pt'           => 'PT',
        'lang.en'           => 'EN',
        // Página Projeto
        'page.projeto.title'       => 'Projeto',
        'page.projeto.description' => 'Descrição do projeto Magalface.',
        'page.projeto.videoTitle'  => 'Vídeo de Apresentação',
        // Página Home
        'page.home.title'          => 'Bem-vindo ao Magalface',
        'page.home.quemSomos'      => 'Quem somos nós',
        'page.home.quemSomos.p1'   => 'Somos estudantes da UNESP Sorocaba apaixonados por tecnologia, inovação e sustentabilidade. Nosso grupo reúne talentos de diferentes áreas para desenvolver soluções inteligentes para o agronegócio e outros setores, sempre com foco em aprendizado prático, colaboração e impacto social.',
        'page.home.quemSomos.p2'   => 'O Magalface nasceu da vontade de aplicar conceitos de IoT, Inteligência Artificial e automação em projetos reais, promovendo integração entre hardware, software e análise de dados. Acreditamos que a tecnologia pode transformar o campo e contribuir para um futuro mais eficiente e sustentável.',
        'page.home.apresentacao.p1'=> 'Magalface é um projeto da área de IoT em desenvolvimento por estudantes da UNESP Sorocaba, focado em arquitetar todo workflow da coleta até a análise de dados usando recursos de Inteligência Artificial. Nosso objetivo é criar dispositivos que coletam dados específicos e os analisam para fornecer insights em tempo real.',
        'page.home.apresentacao.p2'=> 'O projeto envolve diversas etapas, incluindo o planejamento de sensores, a integração de hardware e software, o desenvolvimento de algoritmos de análise e a criação de dashboards interativos para visualização dos dados. A equipe trabalha de forma colaborativa para superar desafios técnicos e propor soluções inovadoras para o agronegócio.',
        'page.home.apresentacao.p3'=> 'Além disso, o Magalface busca promover a sustentabilidade e a eficiência no campo, utilizando tecnologia de ponta para monitorar variáveis ambientais, otimizar processos agrícolas e reduzir desperdícios. O projeto também incentiva a participação de novos alunos, promovendo aprendizado prático e interdisciplinar.',
        'page.home.apresentacao.p4'=> 'Com o avanço das pesquisas, pretendemos expandir o escopo do Magalface para outras áreas, como cidades inteligentes e monitoramento ambiental, sempre mantendo o compromisso com a inovação e a responsabilidade social. Acompanhe nosso progresso e descubra como a tecnologia pode transformar o futuro do campo!',
    ],
    'en' => [
        'nav.projeto'       => 'Project',
        'nav.planejamento'  => 'Planning',
        'nav.fluxograma'    => 'Flowchart',
        'nav.esquemas'      => 'Schematics',
        'nav.dispositivos'  => 'Devices',
        'nav.dashboard'     => 'Dashboard',
        'action.login'      => 'Log in',
        'action.register'   => 'Sign up',
        'action.logout'     => 'Log out',
        'theme.light'       => 'Light',
        'theme.dark'        => 'Dark',
        'lang.pt'           => 'PT',
        'lang.en'           => 'EN',
        // Project page
        'page.projeto.title'       => 'Project',
        'page.projeto.description' => 'Description of the Magalface project.',
        'page.projeto.videoTitle'  => 'Presentation Video',
        // Home page
        'page.home.title'          => 'Welcome to Magalface',
        'page.home.quemSomos'      => 'Who we are',
        'page.home.quemSomos.p1'   => 'We are students from UNESP Sorocaba passionate about technology, innovation and sustainability. Our group brings together talents from different areas to develop smart solutions for agribusiness and other sectors, always focused on hands-on learning, collaboration and social impact.',
        'page.home.quemSomos.p2'   => 'Magalface was born from the desire to apply concepts of IoT, Artificial Intelligence and automation in real projects, promoting integration between hardware, software and data analysis. We believe technology can transform the field and contribute to a more efficient and sustainable future.',
        'page.home.apresentacao.p1'=> 'Magalface is an IoT project under development by students from UNESP Sorocaba, focused on designing the entire workflow from data collection to data analysis using Artificial Intelligence resources. Our goal is to create devices that collect specific data and analyze it to provide real-time insights.',
        'page.home.apresentacao.p2'=> 'The project involves several stages, including sensor planning, hardware and software integration, development of analysis algorithms and creation of interactive dashboards for data visualization. The team works collaboratively to overcome technical challenges and propose innovative solutions for agribusiness.',
        'page.home.apresentacao.p3'=> 'In addition, Magalface seeks to promote sustainability and efficiency in the field, using cutting-edge technology to monitor environmental variables, optimize agricultural processes and reduce waste. The project also encourages the participation of new students, promoting practical and interdisciplinary learning.',
        'page.home.apresentacao.p4'=> 'As research advances, we intend to expand the scope of Magalface to other areas, such as smart cities and environmental monitoring, always maintaining a commitment to innovation and social responsibility. Follow our progress and discover how technology can transform the future of the field!',
    ],
];

/**
 * Traduz uma chave simples com fallback para a própria chave.
 */
function t(string $key): string
{
    /** @var array<string,array<string,string>> $APP_TRANSLATIONS */
    global $APP_TRANSLATIONS;
    $lang = current_lang();
    if (!isset($APP_TRANSLATIONS[$lang])) {
        $lang = APP_LANG_DEFAULT;
    }
    return $APP_TRANSLATIONS[$lang][$key] ?? $key;
}

