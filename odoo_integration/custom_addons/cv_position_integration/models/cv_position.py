import os
import json
import urllib.request
import urllib.error
import ssl
from odoo import models, fields, api, _
from odoo.exceptions import UserError

class CvPosition(models.Model):
    _name = 'cv.position'
    _description = 'CV Position'
    _order = 'id desc'

    name = fields.Char(string='Position Title', required=True)
    company = fields.Char(string='Company')
    level = fields.Char(string='Level')
    short_description = fields.Text(string='Description')
    max_projects = fields.Integer(string='Max Projects Allowed', default=3)
    is_public = fields.Boolean(string='Is Public', default=True)
    project_tags = fields.Char(
        string='Relevant Project Technology Tags',
        help='Candidate projects containing these tags will be automatically selected for the generated CV (up to max projects).'
    )

    required_attribute_ids = fields.Many2many(
        'cv.attribute',
        'cv_position_required_attribute_rel',
        'position_id',
        'attribute_id',
        string='Required Attributes'
    )

    access_rule_ids = fields.One2many(
        'cv.position.access.rule',
        'position_id',
        string='Access Rules'
    )

    total_cvs = fields.Integer(string='Submitted CVs Count', readonly=True, copy=False)
    api_token = fields.Char(string='API Token', readonly=True, index=True, copy=False)
    external_id = fields.Integer(string='Course Project Position ID', readonly=True, copy=False)
    last_imported_at = fields.Datetime(string='Last Synchronized', readonly=True, copy=False)

    attribute_ids = fields.One2many(
        'cv.position.attribute',
        'position_id',
        string='Attributes & Aggregated Results',
        readonly=True,
        copy=False
    )
    cv_line_ids = fields.One2many(
        'cv.position.cv',
        'position_id',
        string='Submitted Applications (CVs)',
        readonly=True,
        copy=False
    )

    def _get_candidate_symfony_urls(self):
        """Return prioritized list of Symfony base URLs to attempt"""
        urls = []
        env_url = os.environ.get('SYMFONY_BASE_URL')
        if env_url:
            urls.append(env_url.strip().rstrip('/'))

        custom_url = self.env['ir.config_parameter'].sudo().get_param('cv.symfony_base_url')
        if custom_url and custom_url not in urls:
            urls.append(custom_url.strip().rstrip('/'))

        candidates = [
            'https://host.docker.internal:8000',
            'http://host.docker.internal:8000',
            'https://172.17.0.1:8000',
            'http://172.17.0.1:8000',
            'https://172.20.0.1:8000',
            'http://172.20.0.1:8000',
        ]
        for c in candidates:
            if c not in urls:
                urls.append(c)
        return urls

    def _send_to_symfony(self, path, method='POST', payload=None):
        """Send HTTP request to Symfony trying candidate URLs and handling SSL certificates."""
        base_urls = self._get_candidate_symfony_urls()

        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=ctx))

        data_bytes = json.dumps(payload).encode('utf-8') if payload is not None else None
        last_error = None

        for base_url in base_urls:
            endpoint = f"{base_url}{path}"
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'User-Agent': 'Odoo-Integration/1.0',
            }
            req = urllib.request.Request(
                endpoint,
                data=data_bytes,
                headers=headers,
                method=method
            )
            try:
                with opener.open(req, timeout=10) as res:
                    resp_body = res.read().decode('utf-8')
                    parsed = json.loads(resp_body) if resp_body else {}
                    # Save working URL to ir.config_parameter
                    self.env['ir.config_parameter'].sudo().set_param('cv.symfony_base_url', base_url)
                    return parsed
            except urllib.error.HTTPError as e:
                try:
                    err_body = json.loads(e.read().decode('utf-8'))
                    err_msg = err_body.get('message', str(e))
                except Exception:
                    err_msg = str(e)
                raise UserError(f"Symfony API returned HTTP error ({e.code}): {err_msg}")
            except Exception as e:
                last_error = e
                continue

        raise UserError(
            f"Could not connect to Symfony server (Connection Refused).\n\n"
            f"Last error: {str(last_error)}\n\n"
            f"Please ensure Symfony is running with:\n"
            f"  symfony server:start --allow-all-ip"
        )

    def action_refresh_data(self):
        """Action button on the form to re-import latest data using the same token"""
        self.ensure_one()
        if not self.api_token:
            raise UserError("This position does not have an API token yet. Please export it first.")
        wizard = self.env['cv.import.wizard'].create({
            'api_token': self.api_token,
        })
        wizard.action_import()
        return {
            'type': 'ir.actions.client',
            'tag': 'display_notification',
            'params': {
                'title': 'Data Refreshed!',
                'message': f'Latest data for "{self.name}" refreshed successfully!',
                'type': 'success',
                'sticky': False,
                'next': {'type': 'ir.actions.client', 'tag': 'reload'},
            }
        }

    def action_sync_attributes_global(self):
        """Sync attributes from Symfony API"""
        return self.env['cv.attribute'].action_sync_from_symfony()

    def action_export_to_symfony(self):
        """Export position in Odoo back to Course Project (Symfony App)"""
        self.ensure_one()
        if self.external_id:
            raise UserError(f"This position is already exported to Course Project (ID #{self.external_id}).")

        tags = [t.strip() for t in (self.project_tags or '').split(',') if t.strip()]

        attributes_list = [
            attr.external_id if attr.external_id else attr.name
            for attr in self.required_attribute_ids
        ]

        access_rules_list = [
            {
                'attributeId': rule.attribute_id.external_id if rule.attribute_id.external_id else rule.attribute_id.name,
                'operator': rule.operator,
                'value': rule.value,
            }
            for rule in self.access_rule_ids
        ]

        payload = {
            'title': self.name,
            'company': self.company or '',
            'level': self.level or '',
            'shortDescription': self.short_description or '',
            'maxProjects': self.max_projects,
            'isPublic': self.is_public,
            'projectTags': tags,
            'attributes': attributes_list,
            'accessRules': access_rules_list,
        }

        resp_data = self._send_to_symfony('/api/positions/external', method='POST', payload=payload)
        pos = resp_data.get('position', {})

        self.write({
            'external_id': pos.get('id'),
            'api_token': pos.get('apiToken'),
            'last_imported_at': fields.Datetime.now(),
        })

        return {
            'type': 'ir.actions.client',
            'tag': 'display_notification',
            'params': {
                'title': 'Export Successful!',
                'message': f"Position successfully exported to Course Project with ID #{pos.get('id')}!",
                'type': 'success',
                'sticky': False,
            }
        }

    @api.returns('self', lambda value: value.id)
    def copy(self, default=None):
        """When duplicating in Odoo, clear external sync data and auto-export to Symfony."""
        self.ensure_one()
        default = dict(default or {})
        if 'name' not in default:
            default['name'] = _("%s (Copy)") % self.name
        default['external_id'] = False
        default['api_token'] = False
        default['last_imported_at'] = False
        default['total_cvs'] = 0

        new_record = super(CvPosition, self).copy(default)

        # Deep copy access rules
        for rule in self.access_rule_ids:
            rule.copy({'position_id': new_record.id})

        # Copy required attributes
        if self.required_attribute_ids:
            new_record.required_attribute_ids = [(6, 0, self.required_attribute_ids.ids)]

        # Automatically export the duplicated position to Symfony Course Project
        try:
            new_record.action_export_to_symfony()
        except Exception:
            # If auto-export fails (e.g. Symfony is temporarily unreachable),
            # leave the duplicated record as draft with external_id=False
            # so the user can easily click 'Export to Course Project'.
            pass

        return new_record

    def write(self, vals):
        """When fields like is_public or name are updated in Odoo, sync them to Symfony."""
        res = super(CvPosition, self).write(vals)

        sync_fields = {'is_public', 'name', 'company', 'level', 'short_description', 'max_projects', 'project_tags'}
        if any(f in vals for f in sync_fields) and not self.env.context.get('from_symfony_sync'):
            for record in self:
                if record.external_id:
                    payload = {
                        'external_id': record.external_id,
                        'api_token': record.api_token,
                    }
                    if 'is_public' in vals:
                        payload['isPublic'] = record.is_public
                    if 'name' in vals:
                        payload['title'] = record.name
                    if 'company' in vals:
                        payload['company'] = record.company or ''
                    if 'level' in vals:
                        payload['level'] = record.level or ''
                    if 'short_description' in vals:
                        payload['shortDescription'] = record.short_description or ''
                    if 'max_projects' in vals:
                        payload['maxProjects'] = record.max_projects
                    if 'project_tags' in vals:
                        payload['projectTags'] = [t.strip() for t in (record.project_tags or '').split(',') if t.strip()]

                    try:
                        record._send_to_symfony('/api/positions/external/update', method='POST', payload=payload)
                    except Exception:
                        # Silently pass if Symfony is temporarily offline so local UI is never blocked
                        pass

        return res

    def unlink(self):
        """When a position is deleted in Odoo, also delete it from the Symfony Course Project."""
        if not self.env.context.get('from_symfony_webhook'):
            for record in self:
                if record.external_id or record.api_token:
                    try:
                        record._send_to_symfony(
                            '/api/positions/external/delete',
                            method='POST',
                            payload={
                                'external_id': record.external_id,
                                'api_token': record.api_token,
                            }
                        )
                    except Exception:
                        # Silently pass if Symfony position is already deleted or temporarily offline
                        pass
        return super(CvPosition, self).unlink()
