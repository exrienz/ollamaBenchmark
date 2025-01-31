
## **Build the Docker Image**
Navigate to the directory containing your `Dockerfile` and run:
```sh
docker build -t ollama-benchmark .
```

---

## **Run the Container and Auto Start on Reboot**
```sh
docker run -d --name ollama-benchmark -p 8282:80 --restart=always ollama-benchmark
```

### **Explanation**
- `-d` → Run in detached mode (background).
- `--name ollama-benchmark` → Name the container for easy reference.
- `-p 8080:80` → Map port 80 in the container to port 8080 on the host.
- `--restart=always` → Ensures the container restarts automatically on reboot or failure.

---

## **Access the Application**
Once the container is running, open:
```
http://localhost:8282
```
If running on a remote server, replace `localhost` with your server's IP.

---

## **Stop and Restart the Container**
If you need to stop or restart:
```sh
docker stop ollama-benchmark
docker start ollama-benchmark
```

This setup ensures your PHP app starts automatically when the server boots up. Let me know if you need further customization!
