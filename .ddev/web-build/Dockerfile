RUN rm -f /usr/share/keyrings/nginx-archive-keyring.gpg
RUN curl -fsSL https://nginx.org/keys/nginx_signing.key | gpg --dearmor | sudo tee /usr/share/keyrings/nginx-archive-keyring.gpg > /dev/null
RUN echo "deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] http://nginx.org/packages/debian bookworm nginx" | sudo tee /etc/apt/sources.list.d/nginx.list
RUN apt-get update
RUN apt-get install exiftool -y
