<main>
	<h2><?php echo function_exists('t') ? t('page.projeto.title') : 'Projeto'; ?></h2>
	<p><?php echo function_exists('t') ? t('page.projeto.description') : 'Descrição do projeto Magalface.'; ?></p>

	<div class="m��dia-centralizada">
		<div style="width:100%;max-width:900px;margin:0 auto;">
			<h3><?php echo function_exists('t') ? t('page.projeto.videoTitle') : 'Vídeo de Apresentação'; ?></h3>
			<div style="position:relative;width:100%;padding-bottom:56.25%;height:0;">
				<iframe
					src="https://www.youtube.com/embed/nuXyYH5AmjM"
					title="Magalface"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen
					style="position:absolute;top:0;left:0;width:100%;height:100%;border-radius:12px;"
				></iframe>
			</div>
		</div>
	</div>
</main>

