<div id="mainmenu">
					<ul>
						<li>
			        		<a href="index.php" title="Вернуться на главную страницу">Home</a>
			    		</li>
			    		<li>
			       			<a href="index.php?act=import" title="Добавление галер">Добавление галер</a>
			        		<ul>
			            		<li><a href="index.php?act=zip">ZIP</a></li>
			            		<li><a href="index.php?act=import">Импорт</a></li>
			            		<li><a href="index.php?act=grabber">Граббер</a></li>
			        		</ul>
						</li>
						<li>
			       			<a href="index.php?act=galleries&amp;tags=true" title="Обработка галер">Обработка галер</a>
			        		<ul style="text-align: left;">
			            		<li>
			            			<a href="index.php?act=galleries&amp;tags=true">Теги</a>
				            		<ul>
				            			<li><a href="index.php?act=galleries&amp;tags=true">Все</a></li>
				            			<li><a href="index.php?act=galleries&amp;tags=true&amp;type=movies">Видео</a></li>
				            			<li><a href="index.php?act=galleries&amp;tags=true&amp;type=pics">Пикс</a></li>
				            			<li><a href="index.php?act=galleries&amp;tags=true&amp;design_type=multi">Быстрая версия</a></li>
				            		</ul>
			            		</li>
			            		<li><a href="index.php?act=galleries&amp;tags=true&amp;skeeped=true">Пропущеные работником</a></li>
				            	<li><a href="/xacropper/">Кроп тумб</a></li>
			        		</ul>
						</li>
						<li>
			       			<a href="index.php?act=galleries" title="Работа с базами">Базы</a>
			        		<ul>
			            		<li><a href="index.php?act=galleries">Галеры</a></li>
			            		<li><a href="index.php?act=paysites">Платники</a></li>
			            		<li><a href="index.php?act=sites">Сайты</a></li>
			            		<li><a href="index.php?act=models">Модели</a></li>
			            		<li>
			            			<a href="index.php?act=banners">Баннеры</a>
			            			<ul>
			            				<li><a href="index.php?act=banners">База</a></li>
			            				<li><a href="index.php?act=banners&amp;query=add">Добавить баннер</a></li>
			            				<li><a href="index.php?act=banners&amp;errors=1">Ошибки</a></li>
			            			</ul>
			            		</li>
			            		<li><a href="index.php?act=templates">Темплейты</a></li>
			            		<li><a href="index.php?act=spots">Споты</a></li>
			            		<li>
			            			<a href="index.php?act=tags">Теги</a>
			            			<ul>
			            				<li><a href="index.php?act=tags&amp;query=add">Добавить тег</a></li>
			            				<li><a href="index.php?act=tags">База</a></li>
			            				<li><a href="index.php?act=tags&amp;query=candidates">Кандидаты в теги</a></li>
			            				<li><a href="index.php?act=tags&amp;query=synonyms">База синонимов</a></li>
			            				<li><a href="index.php?act=tags&amp;query=blacklist">Блеклисты</a></li>
			            			</ul>
			            		</li>
			            		<li><a href="index.php?act=users">Работники</a></li>
			            		<li><a href="index.php?act=trash">Трэш</a></li>
			        		</ul>
						</li>
						<li>
			        		<a href="index.php?act=queries&amp;type=grab">Очереди</a> 
			        		<ul>
			        			<li><a href="index.php?act=queries&amp;type=grab">.. Граббера</a></li>
			        			<li><a href="index.php?act=queries&amp;type=descs">.. Десков</a></li>
			        			<li><a href="index.php?act=queries&amp;type=make_galleries">.. Галер на сайты</a></li>
			        			<li><a href="index.php?act=queries&amp;type=show_cache_query">Очереди на кэш и логи изменения</a></li>
			        		</ul>
			    		</li>
						<li>
			        		
			        		<a href="index.php?act=make">Сборка</a>
			        		<ul>
			        			<li><a href="index.php?act=make">Сборка галер</a></li>
			        			<li><a href="index.php?act=listing&amp;rss=1">Сборка RSS для сайтов</a></li>
			        		</ul>
                        </li>
		    			
			    		<li>
			        		<a href="index.php?act=stats">Статсы</a> 
			        		<ul>
			        			<li><a href="index.php?act=stats">Статистика сайтов</a></li>
			        			<li><a href="index.php?act=server_state">Серверы</a></li>
			        			<li><a href="index.php?act=cronjobs">Кронджобы</a></li>
			        		</ul>
			    		</li>
			    		<li>
			        		<a href="index.php?act=logs">Логи</a> 
			        		<ul>
			        			<li><a href="index.php?act=logs">Общие</a></li>
			        			<li><a href="index.php?act=logs&amp;type=errors">Ошибки</a></li>
			        			<li><a href="index.php?act=logs&amp;type=crons">Кроны</a></li>
			        			<li><a href="index.php?act=logs&amp;type=crons_errors">Ошибки кронов</a></li>
			        			<li><a href="index.php?act=logs&amp;type=php_errors">Ошибки PHP</a></li>
			        			
			        		</ul>
			    		</li>
			    		<li>
			    			<div style="padding-top:6px; padding-left:16px; float: right;">
			    				<input id="go_to_gal_id" size="4" value="">
			    				<input type="button" value="Go" onclick="goToGallery();">
			    			</div>
			    		</li>
			    		<li>
			        		<a href="index.php?logout=1">Выход</a>
			    		</li>
					</ul>
				</div>
