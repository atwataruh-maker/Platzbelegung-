import {defineConfig} from 'vite';
import react from '@vitejs/plugin-react';
import {resolve} from 'node:path';
export default defineConfig({root:resolve(import.meta.dirname,'frontend'),base:'./',plugins:[react()],resolve:{alias:{'@':resolve(import.meta.dirname)}},build:{outDir:resolve(import.meta.dirname,'frontend-dist'),emptyOutDir:true,assetsDir:'assets'},server:{proxy:{'/api':'http://127.0.0.1:8080'}}});
