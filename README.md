setup pake 2 terminal (first time launch)
Terminal 1 cd smart-agri-ai

	python -m venv venv
	.\venv\Scripts\Activate.ps1
	pip install fastapi uvicorn scikit-learn numpy pandas pyswarms 	
	uvicorn main:app --reload --host 127.0.0.1 --port 8000

Terminal 2 cd smart-agri-web

	composer install
	php artisan serve --port=8080
	

buka: http://127.0.0.1:8001/optimasi
