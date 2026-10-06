import os
import json
import urllib.request
import urllib.error
import ssl
from odoo import models, fields, api
from odoo.exceptions import UserError

class CvAttribute(models.Model):
    _name = 'cv.attribute'
    _description = 'CV Attribute'
    _order = 'name asc'

    name = fields.Char(string='Attribute Name', required=True)
    external_id = fields.Integer(string='Symfony Attribute ID', index=True)
    attr_type = fields.Char(string='Type', default='text')

    def action_sync_from_symfony(self):
        """Fetch all available attributes from Symfony API and sync to Odoo"""
        base_urls = []
        env_url = os.environ.get('SYMFONY_BASE_URL')
        if env_url:
            base_urls.append(env_url.strip().rstrip('/'))

        custom_url = self.env['ir.config_parameter'].sudo().get_param('cv.symfony_base_url')
        if custom_url and custom_url not in base_urls:
            base_urls.append(custom_url.strip().rstrip('/'))

        candidates = [
            'https://host.docker.internal:8000',
            'http://host.docker.internal:8000',
            'https://172.17.0.1:8000',
            'http://172.17.0.1:8000',
            'https://172.20.0.1:8000',
            'http://172.20.0.1:8000',
        ]
        for c in candidates:
            if c not in base_urls:
                base_urls.append(c)

        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=ctx))

        last_error = None
        data = None
        working_url = None

        for base_url in base_urls:
            endpoint = f"{base_url}/api/positions/attributes"
            req = urllib.request.Request(
                endpoint,
                headers={'Accept': 'application/json', 'User-Agent': 'Odoo-Integration/1.0'}
            )
            try:
                with opener.open(req, timeout=5) as res:
                    data = json.loads(res.read().decode('utf-8'))
                    working_url = base_url
                    break
            except Exception as e:
                last_error = e
                continue

        if data is None:
            raise UserError(f"Could not connect to Symfony server to sync attributes.\n\nDetails: {str(last_error)}\n\nPlease ensure Symfony server is running with: symfony server:start --allow-all-ip")

        if working_url:
            self.env['ir.config_parameter'].sudo().set_param('cv.symfony_base_url', working_url)

        created_count = 0
        updated_count = 0
        for item in data:
            ext_id = item.get('id')
            name = item.get('name')
            attr_type = item.get('type') or 'text'
            if not name:
                continue

            existing = self.search([
                '|',
                ('external_id', '=', ext_id),
                ('name', '=ilike', name)
            ], limit=1)

            if existing:
                existing.write({
                    'name': name,
                    'external_id': ext_id,
                    'attr_type': attr_type,
                })
                updated_count += 1
            else:
                self.create({
                    'name': name,
                    'external_id': ext_id,
                    'attr_type': attr_type,
                })
                created_count += 1

        return {
            'type': 'ir.actions.client',
            'tag': 'display_notification',
            'params': {
                'title': 'Attributes Synced!',
                'message': f"Synced {len(data)} attributes from Symfony ({created_count} added, {updated_count} updated).",
                'type': 'success',
                'sticky': False,
            }
        }
