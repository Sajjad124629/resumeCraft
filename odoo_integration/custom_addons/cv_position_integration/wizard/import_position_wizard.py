import os
import json
import urllib.request
import urllib.error
import ssl
from odoo import models, fields, api
from odoo.exceptions import UserError

class ImportPositionWizard(models.TransientModel):
    _name = 'cv.import.wizard'
    _description = 'Import Position via API Token'

    def _default_api_url(self):
        env_url = os.environ.get('SYMFONY_BASE_URL')
        if env_url:
            return env_url.strip().rstrip('/')
        param_url = self.env['ir.config_parameter'].sudo().get_param('cv.symfony_base_url')
        if param_url:
            return param_url.strip().rstrip('/')
        return 'http://172.17.0.1:8000'

    api_url = fields.Char(
        string='Course Project Base URL',
        default=_default_api_url,
        required=True,
        help='URL of the course project Symfony app (e.g. your live domain https://example.com or local URL)'
    )
    api_token = fields.Char(
        string='Position API Token',
        required=True,
        help='Paste the API token generated on the Position form in the Course Project'
    )

    def _fetch_from_api(self, url):
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE

        opener = urllib.request.build_opener(
            urllib.request.HTTPSHandler(context=ctx)
        )

        req = urllib.request.Request(
            url,
            headers={
                'User-Agent': 'Odoo-Integration/1.0',
                'Accept': 'application/json',
            }
        )
        with opener.open(req, timeout=10) as response:
            return json.loads(response.read().decode('utf-8'))

    def action_import(self):
        self.ensure_one()
        token = self.api_token.strip()
        if not token:
            raise UserError('Please enter a valid API token.')

        base_urls = [self.api_url.strip().rstrip('/')]
        env_url = os.environ.get('SYMFONY_BASE_URL')
        if env_url and env_url not in base_urls:
            base_urls.insert(0, env_url.strip().rstrip('/'))

        custom_url = self.env['ir.config_parameter'].sudo().get_param('cv.symfony_base_url')
        if custom_url and custom_url not in base_urls:
            base_urls.insert(0, custom_url.strip().rstrip('/'))

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

        payload = None
        error_details = []

        for base_url in base_urls:
            endpoint = f"{base_url}/api/positions/{token}/aggregated"
            try:
                payload = self._fetch_from_api(endpoint)
                self.api_url = base_url
                self.env['ir.config_parameter'].sudo().set_param('cv.symfony_base_url', base_url)
                break
            except urllib.error.HTTPError as e:
                try:
                    body = json.loads(e.read().decode('utf-8'))
                    msg = body.get('message', str(e))
                except Exception:
                    msg = str(e)
                raise UserError(f"Course Project API returned error ({e.code}): {msg}")
            except Exception as e:
                error_details.append(f"{base_url}: {str(e)}")

        if not payload:
            raise UserError(
                "Could not connect to Course Project API (Connection Refused).\n\n"
                "Attempted URLs:\n" + "\n".join(error_details) +
                "\n\nPlease ensure Symfony is running with:\n  symfony server:start --allow-all-ip"
            )

        pos_data = payload.get('position', {})
        attrs_data = payload.get('attributes', [])

        if not pos_data:
            raise UserError("Invalid response format: 'position' object is missing.")

        CvPosition = self.env['cv.position']
        existing = CvPosition.search([('api_token', '=', token)], limit=1)

        project_tags_list = pos_data.get('projectTags', [])
        project_tags_str = ", ".join(project_tags_list) if isinstance(project_tags_list, list) else str(project_tags_list)

        vals = {
            'name': pos_data.get('title') or 'Untitled Position',
            'company': pos_data.get('company'),
            'level': pos_data.get('level'),
            'short_description': pos_data.get('shortDescription'),
            'is_public': pos_data.get('isPublic', True),
            'total_cvs': pos_data.get('totalCvs', 0),
            'project_tags': project_tags_str,
            'api_token': token,
            'external_id': pos_data.get('id'),
            'last_imported_at': fields.Datetime.now(),
        }

        if existing:
            existing.write(vals)
            position_record = existing
            position_record.attribute_ids.unlink()
            position_record.cv_line_ids.unlink()
        else:
            position_record = CvPosition.create(vals)

        # Populate attributes and aggregated results
        attr_vals = []
        for attr in attrs_data:
            pop_vals_str = ""
            if attr.get('popular_values'):
                pop_vals_str = "\n".join([f"• {pv.get('value')}: {pv.get('count')} candidate(s)" for pv in attr.get('popular_values', [])])

            attr_vals.append((0, 0, {
                'attribute_title': attr.get('title'),
                'attribute_type': attr.get('type'),
                'aggregation_kind': 'numeric' if attr.get('aggregation_type') == 'numeric' else 'text',
                'min_value': attr.get('min') or 0.0,
                'max_value': attr.get('max') or 0.0,
                'avg_value': attr.get('avg') or 0.0,
                'count': attr.get('count', 0),
                'popular_values': pop_vals_str,
                'summary': attr.get('summary') or 'N/A',
            }))

        # Populate submitted CVs
        cv_vals = []
        for c in pos_data.get('cvs', []):
            cv_vals.append((0, 0, {
                'external_cv_id': c.get('id'),
                'candidate_name': c.get('candidateName') or f"Candidate #{c.get('id')}",
                'status': (c.get('status') or 'draft').capitalize(),
                'likes': c.get('likes', 0),
                'created_at': c.get('createdAt') or '',
            }))

        position_record.write({
            'attribute_ids': attr_vals,
            'cv_line_ids': cv_vals,
        })

        # Open the imported position form view
        return {
            'type': 'ir.actions.act_window',
            'name': 'Position Details',
            'res_model': 'cv.position',
            'res_id': position_record.id,
            'view_mode': 'form',
            'target': 'current',
        }
